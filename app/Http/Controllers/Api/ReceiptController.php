<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Receipt;
use App\Models\Rfq;
use App\Models\RfqComponent;
use App\Models\Sales;
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
            'scheduled_date' => Carbon::parse($receipt->scheduled_date)->format('Y-m-d H:i:s'),
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
        return [
            'id' => $receipt->receipt_id,
            'transaction_type' => $receipt->transaction_type,
            'reference' => $receipt->reference,
            'customer_id' => $receipt->customer_id,
            'customer_name' => $receipt->customer->name,
            'source_document' => $receipt->source_document,
            'sales_id' => $receipt->sales_id,
            'invoice_status' => $receipt->sales->invoice_status,
            'scheduled_date' => Carbon::parse($receipt->scheduled_date)->format('Y-m-d H:i:s'),
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
                            $index = str_replace(['items.', '.qty_received'], '', $attribute);

                            $type = $request->input("items.$index.type");
                            if ($type === 'line_section') {
                                return;
                            }
                            $componentId = $request->input("items.$index.component_id");
                            $rfqComponent = RfqComponent::where('rfq_component_id', $componentId)->first();
                            if ($rfqComponent && $value > $rfqComponent->qty) {
                                $fail('Qty received must not exceed the available qty.');
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
                return response()->json([
                    'success' => false,
                    'message' => 'Receipt not found',
                ], 404);
            }
            $scheduled_date = Carbon::parse($data['scheduled_date']);
            $rfq = Rfq::findOrFail($data['rfq_id']);
            $rfqReference = $rfq->reference;
            if ($data['transaction_type'] == 'IN') {
                if ($data['state'] == 2) {
                    $receipt->update([
                        'transaction_type' => $data['transaction_type'],
                        'rfq_id' => $rfq->rfq_id,
                        'vendor_id' => $data['vendor_id'],
                        'source_document' => $rfqReference,
                        'scheduled_date' => $scheduled_date,
                        'state' => $data['state']
                    ]);
                    foreach ($data['items'] as $component) {
                        $rfqComponent = RfqComponent::where('rfq_id', $rfq->rfq_id)->where('rfq_component_id', $component['component_id'])->first();
                        if ($rfqComponent) {
                            $rfqComponent->update([
                                'material_id' => $component['material_id'],
                                'qty_received' =>  $component['qty_received'],
                                'qty_to_invoice' => $component['qty_received'],
                            ]);
                        }
                    }
                } else if ($data['state'] == 3) {
                    $receipt->update([
                        'transaction_type' => $data['transaction_type'],
                        'rfq_id' => $rfq->rfq_id,
                        'vendor_id' => $data['vendor_id'],
                        'source_document' => $rfqReference,
                        'scheduled_date' => $scheduled_date,
                        'state' => $data['state']
                    ]);
                    foreach ($data['items'] as $component) {
                        $rfqComponent = RfqComponent::where('rfq_id', $rfq->rfq_id)->where('rfq_component_id', $component['component_id'])->first();
                        if ($rfqComponent) {
                            $rfqComponent->update([
                                'material_id' => $component['material_id'],
                                'qty_received' =>  $component['qty_received'] + $rfqComponent->qty_received,
                                'qty_to_invoice' => $component['qty_received'] + $rfqComponent->qty_to_invoice,
                            ]);
                        }
                        $material = Material::find($component['material_id']);
                        if ($material) {
                            $material->update([
                                'stock' => $material->stock + $component['qty_received'],
                            ]);
                        }
                    }
                    if ($rfq) {
                        $rfq->update([
                            'invoice_status' => $data['invoice_status'],
                        ]);
                    }
                } else if ($data['state'] == 4) {
                    $receipt->update([
                        'transaction_type' => $data['transaction_type'],
                        'rfq_id' => $rfq->rfq_id,
                        'vendor_id' => $data['vendor_id'],
                        'source_document' => $rfqReference,
                        'scheduled_date' => $scheduled_date,
                        'state' => $data['state']
                    ]);
                    if ($rfq) {
                        $rfq->update([
                            'invoice_status' => $data['invoice_status'],
                        ]);
                    }
                }
            } else if ($data['transaction_type'] == 'OUT') {
                // if ($data['state'] == 2) {
                //     $receipt->update([
                //         'transaction_type' => $data['transaction_type'],
                //         'rfq_id' => $rfq->rfq_id,
                //         'vendor_id' => $data['vendor_id'],
                //         'source_document' => $rfqReference,
                //         'scheduled_date' => $scheduled_date,
                //         'state' => $data['state']
                //     ]);
                // } else if ($data['state'] == 3) {
                //     $receipt->update([
                //         'transaction_type' => $data['transaction_type'],
                //         'rfq_id' => $rfq->rfq_id,
                //         'vendor_id' => $data['vendor_id'],
                //         'source_document' => $rfqReference,
                //         'scheduled_date' => $scheduled_date,
                //         'state' => $data['state']
                //     ]);
                //     foreach ($data['items'] as $component) {
                //         $rfqComponent = RfqComponent::where('rfq_id', $rfq->rfq_id)->where('rfq_component_id', $component['component_id'])->first();
                //         if ($rfqComponent) {
                //             $rfqComponent->update([
                //                 'material_id' => $component['material_id'],
                //                 'qty_received' => $component['qty_received'],
                //                 'qty_to_invoice' => $component['qty_to_invoice'],
                //             ]);
                //         }
                //     }
                // }
            }
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
                'message' => 'Failed to update receipt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id) {}
}
