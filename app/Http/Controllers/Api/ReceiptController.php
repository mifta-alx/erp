<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Rfq;
use App\Models\RfqComponent;
use App\Models\Sales;
use App\Models\SalesComponent;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $rfq_id = $request->get('rfq_id');
        $sales_id = $request->get('sales_id');
        $transaction_type = $request->get('transaction_type');
        $receipts = Receipt::when($transaction_type, function ($query) use ($transaction_type) {
            return $query->where('transaction_type', $transaction_type);
        })->when($rfq_id, function ($query) use ($rfq_id) {
            return $query->where('rfq_id', $rfq_id);
        })->when($sales_id, function ($query) use ($sales_id) {
            return $query->where('sales_id', $sales_id);
        })->orderBy('created_at', 'DESC')->get();
        return response()->json([
            'success' => true,
            'message' => 'List Recipt Data',
            'data' => $receipts->map(function ($receipt) {
                if ($receipt->transaction_type == 'IN') {
                    return $this->responseIn($receipt);
                }
                if ($receipt->transaction_type == 'OUT') {
                    return $this->responseOut($receipt);
                }
            }),
        ], 200);
    }

    private function responseIn($receipt)
    {
        return [
            'id' => $receipt->receipt_id,
            'transaction_type' => $receipt->transaction_type,
            'reference' => $receipt->reference,
            'vendor_id' => $receipt->vendor_id,
            'vendor_name' => $receipt->vendor->name,
            'source_document' => $receipt->source_document,
            'rfq_id' => $receipt->rfq_id,
            'invoice_status' => $receipt->rfq->invoice_status,
            'scheduled_date' => $receipt->scheduled_date ? Carbon::parse($receipt->scheduled_date)->format('Y-m-d H:i:s') : null,
            'state' => $receipt->state,
            'items' => $receipt->rfq->rfqComponent
                ->filter(function ($component) {
                    return $component->display_type !== 'line_section';
                })
                ->values()
                ->map(function ($component) {
                    return [
                        'component_id' => $component->rfq_component_id,
                        'type' => $component->display_type,
                        'id' => $component->material_id,
                        'internal_reference' => $component->material->internal_reference,
                        'name' => $component->material->material_name,
                        'description' => $component->description,
                        'qty' => $component->qty,
                        'unit_price' => $component->unit_price,
                        'tax' => $component->tax,
                        'subtotal' => $component->subtotal,
                        'qty_received' => $component->qty_received,
                        'qty_to_invoice' => $component->qty_to_invoice,
                        'qty_invoiced' => $component->qty_invoiced,
                    ];
                }),
        ];
    }
    private function responseOut($receipt)
    {
        $companyName = null;
        if ($receipt->customer->type == 1 && $receipt->customer->company !== null) {
            $customerCompany = Customer::where('customer_id', $receipt->customer->company)->first();
            $companyName = $customerCompany ? $customerCompany->name : null;
        }
        return [
            'id' => $receipt->receipt_id,
            'transaction_type' => $receipt->transaction_type,
            'reference' => $receipt->reference,
            'customer_id' => $receipt->customer_id,
            'customer_name' => $receipt->customer->name,
            'customer_company' => $receipt->customer->company,
            'customer_company_name' => $companyName,
            'customer_type' => $receipt->customer->type,
            'source_document' => $receipt->source_document,
            'sales_id' => $receipt->sales_id,
            'invoice_status' => $receipt->sales->invoice_status,
            'scheduled_date' => $receipt->scheduled_date ? Carbon::parse($receipt->scheduled_date)->format('Y-m-d H:i:s') : null,
            'state' => $receipt->state,
            'items' => $receipt->sales->salesComponents
                ->filter(function ($component) {
                    return $component->display_type !== 'line_section';
                })
                ->values()
                ->map(function ($component) {
                    return [
                        'component_id' => $component->sales_component_id,
                        'type' => $component->display_type,
                        'id' => $component->product_id,
                        'internal_reference' => $component->product->internal_reference,
                        'name' => $component->product->product_name,
                        'description' => $component->description,
                        'qty' => $component->qty,
                        'unit_price' => $component->unit_price,
                        'tax' => $component->tax,
                        'subtotal' => $component->subtotal,
                        'qty_received' => $component->qty_received,
                        'qty_to_invoice' => $component->qty_to_invoice,
                        'qty_invoiced' => $component->qty_invoiced,
                        'reserved' => $component->reserved,
                    ];
                }),
        ];
    }

    private function validateRecipt(Request $request)
    {
        return Validator::make($request->all(), [
            'scheduled_date' => 'required',
        ], [
            'scheduled_date.required' => 'Scheduled Date must be filled',
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->json()->all();
            $validator = $this->validateRecipt($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            if ($data['transaction_type'] == 'IN') {
                $lastOrder = Receipt::where('transaction_type', 'IN')->orderBy('created_at', 'desc')->first();
                $rfq = Rfq::find($data['rfq_id']);
                $rfqReference = $rfq->reference;
            } else {
                $lastOrder = Receipt::where('transaction_type', 'OUT')->orderBy('created_at', 'desc')->first();
                $sales = Sales::findOrFail($data['sales_id']);
                $salesReference = $sales->reference;
            }

            if ($lastOrder && $lastOrder->reference) {
                $lastReferenceNumber = (int) substr($lastOrder->reference, 4);
            } else {
                $lastReferenceNumber = 0;
            }
            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
            $reference = "{$data['transaction_type']}/{$referenceNumberPadded}";
            $scheduled_date = Carbon::parse($data['scheduled_date']);

            $receipt = Receipt::create([
                'transaction_type' => $data['transaction_type'],
                'reference' => $reference,
                'rfq_id' => $rfq->rfq_id ?? null,
                'sales_id' => $sales->sales_id ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'source_document' => $rfqReference ?? $salesReference,
                'scheduled_date' => $scheduled_date,
                'state' => $data['state']
            ]);
            DB::commit();
            if ($data['transaction_type'] == 'IN') {
                return response()->json([
                    'success' => true,
                    'message' => 'Receipt Successfully Added',
                    'data' => $this->responseIn($receipt)
                ], 201);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Receipt Successfully Added',
                    'data' => $this->responseOut($receipt)
                ], 201);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Receipt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show($id)
    {
        $receipt = Receipt::find($id);

        if (!$receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found',
            ], 404);
        }
        if ($receipt->transaction_type == 'IN') {
            return response()->json([
                'success' => true,
                'message' => 'Detail Receipt Data',
                'data' => $this->responseIn($receipt)
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Detail Receipt Data',
                'data' => $this->responseOut($receipt)
            ], 201);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->json()->all();
            $validator = Validator::make(
                $request->all(),
                [
                    'scheduled_date' => 'required',
                    'items.*.qty_received' => [
                        'required',
                        function ($attribute, $value, $fail) use ($request) {
                            $transactionType = $request->input('transaction_type');
                            $index = str_replace(['items.', '.qty_received'], '', $attribute);
                            $componentId = $request->input("items.$index.component_id");
                            if ($transactionType === 'OUT') {
                                $salesComponent = SalesComponent::where('sales_component_id', $componentId)->first();
                                if ($salesComponent && $value > $salesComponent->qty) {
                                    $fail('Qty received must not be less than the required qty in Sales.');
                                    return;
                                }
                            }
                            if ($transactionType === 'IN') {
                                $rfqComponent = RfqComponent::where('rfq_component_id', $componentId)->first();
                                if ($rfqComponent && $value > $rfqComponent->qty) {
                                    $fail('Qty received must not exceed the available qty in RFQ.');
                                }
                            }
                        }
                    ],
                ],
                [
                    'scheduled_date.required' => 'Scheduled Date must be filled',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $receipt = Receipt::find($id);
            if (!$receipt) {
                return response()->json(['success' => false, 'message' => 'Receipt not found'], 404);
            }

            $scheduled_date = Carbon::parse($data['scheduled_date']);
            $this->updateReceipt($receipt, $data, $scheduled_date);
            if ($data['transaction_type'] == 'OUT' && $data['state'] == 4) {
                foreach ($data['items'] as $component) {
                    $this->processItem($data, $component);
                }
            } else if ($data['transaction_type'] == 'IN') {
                foreach ($data['items'] as $component) {
                    $this->processItem($data, $component);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Receipt Successfully Updated',
                'data' => $data['transaction_type'] === 'IN'
                    ? $this->responseIn($receipt)
                    : $this->responseOut($receipt)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update receipt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function updateReceipt($receipt, $data, $scheduled_date)
    {
        $commonData = [
            'transaction_type' => $data['transaction_type'],
            'scheduled_date' => $scheduled_date,
        ];
        if ($data['transaction_type'] === 'IN') {
            $rfq = Rfq::findOrFail($data['rfq_id']);
            $receipt->update(array_merge($commonData, [
                'rfq_id' => $rfq->rfq_id,
                'vendor_id' => $data['vendor_id'],
                'source_document' => $rfq->reference,
                'state' => $data['state']
            ]));
            if (in_array($data['state'], [4, 5])) {
                if ($rfq) {
                    $rfq->update([
                        'invoice_status' => $data['invoice_status'],
                    ]);
                }
            }
        } else {
            $sales = Sales::findOrFail($data['sales_id']);
            $receipt->update(array_merge($commonData, [
                'sales_id' => $sales->sales_id,
                'customer_id' => $data['customer_id'],
                'source_document' => $sales->reference,
                'state' => $data['state']
            ]));
            if ($data['state'] == 2) {
                $canUpdateState = true;
                foreach ($data['items'] as $component) {
                    if ($component['type'] === 'product' && isset($component['id'])) {
                        $salesComponent = SalesComponent::where('product_id', $component['id'])
                            ->where('sales_id', $sales->sales_id)
                            ->first();
                        if ($salesComponent) {
                            $product = Product::find($component['id']);
                            if ($product) {
                                $stockAvailable = $product->stock;
                                $requiredQty = $component['qty'];
                                $reservedQty = min($stockAvailable, $requiredQty);
                                $salesComponent->update(['reserved' => $reservedQty]);
                                if ($reservedQty < $requiredQty) {
                                    $canUpdateState = false;
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($canUpdateState) {
                    $receipt->update([
                        'state' => 3,
                    ]);
                    foreach ($data['items'] as $component) {
                        $this->processItem($data, $component);
                    }
                }
            }
            if (in_array($data['state'], [4, 5])) {
                if ($sales) {
                    $sales->update([
                        'invoice_status' => $data['invoice_status'],
                    ]);
                }
            }
        }
    }

    private function processItem($data, $component)
    {
        if ($data['transaction_type'] === 'IN') {
            $this->processIncomingItem($data, $component);
        } else {
            $this->processOutgoingItem($data, $component);
        }
    }

    private function processIncomingItem($data, $component)
    {
        $rfqComponent = RfqComponent::where('rfq_id', $data['rfq_id'])
            ->where('rfq_component_id', $component['component_id'])
            ->first();

        if ($rfqComponent) {
            $rfqComponent->update([
                'material_id' => $component['id'],
                'qty_received' => ($data['state'] == 4)
                    ? $rfqComponent->qty_received + $component['qty_received']
                    : $component['qty_received'],
                'qty_to_invoice' => ($data['state'] == 4)
                    ? $rfqComponent->qty_to_invoice + $component['qty_received']
                    : $component['qty_received'],
            ]);
        }

        if ($data['state'] == 4) {
            $material = Material::find($component['id']);
            if ($material) {
                $material->update(['stock' => $material->stock + $component['qty_received']]);
            }
        }
    }

    private function processOutgoingItem($data, $component)
    {
        $salesComponent = SalesComponent::where('sales_id', $data['sales_id'])
            ->where('sales_component_id', $component['component_id'])
            ->first();
        if ($salesComponent) {
            $salesComponent->update([
                'product_id' => $component['id'],
                'qty_received' => $component['qty_received'],
                'qty_to_invoice' => $component['qty_received'],
            ]);
        }
        if ($data['state'] == 4) {
            $product = Product::find($component['id']);
            if ($product) {
                $product->update(['stock' => $product->stock - $component['qty_received']]);
            }
        }
    }

    public function destroy($id) {}
}
