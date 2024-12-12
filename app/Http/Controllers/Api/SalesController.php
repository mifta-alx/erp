<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Sales;
use App\Models\SalesComponent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{

    public function index(Request $request)
    {
        $query = Sales::query();
        if ($request->has('sales_order') && $request->sales_order == 'true') {
            $query->where('state', 3);
        }
        $sales = $query->with(['customer', 'salesComponents'])->orderBy('created_at', 'DESC')->get();
        $salesData = $sales->map(function ($sale) {
            return $this->transformSales($sale);
        });
        return response()->json([
            'success' => true,
            'message' => 'List Sales Data',
            'data' => $salesData,
        ]);
    }
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->json()->all();
            $validator = $this->validateSales($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $lastSales = Sales::orderBy('created_at', 'DESC')->first();
            if ($lastSales && $lastSales->reference) {
                $lastReferenceNumber = (int) substr($lastSales->reference, 3);
            } else {
                $lastReferenceNumber = 0;
            }
            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
            $reference = "S{$referenceNumberPadded}";
            $expiration = Carbon::parse($data['expiration']);
            $confirmDate = isset($data['confirmation_date']) ? Carbon::parse($data['confirmation_date']) : null;
            $sales = Sales::create([
                'customer_id' => $data['customer_id'],
                'taxes' => $data['taxes'],
                'total' => $data['total'],
                'expiration' => $expiration,
                'confirmation_date' => $confirmDate,
                'invoice_status' => $data['invoice_status'],
                'state' => $data['state'],
                'payment_term_id' => $data['payment_term_id'],
                'reference' => $reference,
            ]);
            foreach ($data['items'] as $component) {
                if ($component['type'] == 'product') {
                    SalesComponent::create([
                        'sales_id' => $sales->sales_id,
                        'product_id' => $component['id'],
                        'description' => $component['description'],
                        'display_type' => $component['type'],
                        'qty' => $component['qty'],
                        'unit_price' => $component['unit_price'],
                        'tax' => $component['tax'],
                        'subtotal' => $component['subtotal'],
                        'qty_received' => $component['qty_received'] ?? 0,
                        'qty_to_invoice' => $component['qty_to_invoice'] ?? 0,
                        'qty_invoiced' => $component['qty_invoiced'] ?? 0,
                    ]);
                } else {
                    SalesComponent::create([
                        'sales_id' => $sales->sales_id,
                        'product_id' => null,
                        'description' => $component['description'],
                        'display_type' => $component['type'],
                        'qty' => 0,
                        'unit_price' => 0,
                        'tax' => 0,
                        'subtotal' => 0,
                        'qty_received' => 0,
                        'qty_to_invoice' => 0,
                        'qty_invoiced' => 0,
                    ]);
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sales created successfully',
                'data' => $this->transformSales($sales->load(['customer', 'salesComponents'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->json()->all();
            $validator = $this->validateSales($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $sales = Sales::find($id);
            if (!$sales) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales not found',
                ], 404);
            }
            $expiration = Carbon::parse($data['expiration']);
            $confirmDate = isset($data['confirmation_date']) ? Carbon::parse($data['confirmation_date']) : null;
            $sales->update([
                'customer_id' => $data['customer_id'],
                'taxes' => $data['taxes'],
                'total' => $data['total'],
                'expiration' => $expiration,
                'confirmation_date' => $confirmDate,
                'invoice_status' => $data['invoice_status'],
                'state' => $data['state'],
                'payment_term_id' => $data['payment_term_id'],
            ]);
            foreach ($data['items'] as $component) {
                if (isset($component['component_id'])) {
                    $salesComponent = SalesComponent::find($component['component_id']);
                    if ($salesComponent && $salesComponent->sales_id === $sales->sales_id) {
                        $salesComponent->update([
                            'product_id' => $component['type'] == 'product' ? $component['id'] : null,
                            'description' => $component['description'],
                            'display_type' => $component['type'],
                            'qty' => $component['qty'] ?? 0,
                            'unit_price' => $component['unit_price'] ?? 0,
                            'tax' => $component['tax'] ?? 0,
                            'subtotal' => $component['subtotal'] ?? 0,
                            'qty_received' => $component['qty_received'] ?? 0,
                            'qty_to_invoice' => $component['qty_to_invoice'] ?? 0,
                            'qty_invoiced' => $component['qty_invoiced'] ?? 0,
                        ]);
                    }
                } else {
                    SalesComponent::create([
                        'sales_id' => $sales->sales_id,
                        'product_id' => $component['type'] == 'product' ? $component['id'] : null,
                        'description' => $component['description'],
                        'display_type' => $component['type'],
                        'qty' => $component['qty'] ?? 0,
                        'unit_price' => $component['unit_price'] ?? 0,
                        'tax' => $component['tax'] ?? 0,
                        'subtotal' => $component['subtotal'] ?? 0,
                        'qty_received' => $component['qty_received'] ?? 0,
                        'qty_to_invoice' => $component['qty_to_invoice'] ?? 0,
                        'qty_invoiced' => $component['qty_invoiced'] ?? 0,
                    ]);
                }
            }
            if ($data['state'] == 3) {
                if (empty($data['items']) || !collect($data['items'])->contains(function ($item) {
                    return $item['type'] === 'product';
                })) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Sales updated successfully',
                        'data' => $this->transformSales($sales->load(['customer', 'salesComponents'])),
                    ]);
                } else {
                    $existingReceipt = Receipt::where('sales_id', $sales->sales_id)->first();
                    if ($existingReceipt) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Sales updated successfully. Receipt already exists.',
                            'data' => $this->transformSales($sales->load(['customer', 'salesComponents'])),
                        ]);
                    }
                    $scheduledDate = Carbon::parse($data['scheduled_date']);
                    $lastOrder = Receipt::where('transaction_type', 'OUT')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $lastReferenceNumber = $lastOrder && $lastOrder->reference
                        ? (int) substr($lastOrder->reference, 4)
                        : 0;
                    $referenceNumber = $lastReferenceNumber + 1;
                    $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
                    $reference = "OUT/{$referenceNumberPadded}";
                    Receipt::create([
                        'transaction_type' => 'OUT',
                        'reference' => $reference,
                        'sales_id' => $sales->sales_id,
                        'customer_id' => $sales->customer_id,
                        'source_document' => $sales->reference,
                        'scheduled_date' => $scheduledDate,
                        'state' => 2,
                    ]);
                }
            } else if ($data['state'] == 4) {
                $receipts = Receipt::where('sales_id', $sales->sales_id)
                    ->where('state', '!=', 4)
                    ->get();

                foreach ($receipts as $receipt) {
                    $receipt->update(['state' => 4]);
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sales updated successfully',
                'data' => $this->transformSales($sales->load(['customer', 'salesComponents'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id)
    {
        $sales = Sales::with(['customer', 'salesComponents'])->find($id);
        if (!$sales) {
            return response()->json([
                'success' => false,
                'message' => 'Sales not found',
            ], 404);
        }
        $salesData = $this->transformSales($sales);
        return response()->json([
            'success' => true,
            'data' => $salesData,
        ]);
    }
    private function validateSales($request, $isUpdate = false)
    {
        return Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,customer_id',
        ], [
            'customer_id.required' => 'customer ID must be filled',
            'customer_id.exists' => 'customer ID does not exist',
        ]);
    }


    private function transformSales($sale)
    {
        return [
            'id' => $sale->sales_id,
            'reference' => $sale->reference,
            'customer_id' => $sale->customer_id,
            'customer_name' => $sale->customer->name,
            'taxes' => $sale->taxes,
            'total' => $sale->total,
            'expiration' => Carbon::parse($sale->expiration)->format('Y-m-d H:i:s'),
            'confirmation_date' => $sale->confirmation_date
                ? Carbon::parse($sale->confirmation_date)->format('Y-m-d H:i:s')
                : null,
            'invoice_status' => $sale->invoice_status,
            'state' => $sale->state,
            'payment_term_id' => $sale->paymentTerm->payment_term_id ?? null,
            'payment_term_name' => $sale->paymentTerm->name ?? null,
            'receipt' => $sale->receipts->map(function ($receipt) {
                return [
                    'id' => $receipt->receipt_id,
                ];
            }),
            'creation_date' => Carbon::parse($sale->created_at)->format('Y-m-d H:i:s'),
            'items' => $sale->salesComponents->map(function ($component) {
                return $this->transformSalesComponent($component);
            }),
            'invoices' => $sale->invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->invoice_id,
                    'payment_status' => $invoice->payment_status
                ];
            })
        ];
    }

    private function transformSalesComponent($component)
    {
        return [
            'component_id' => $component->sales_component_id,
            'type' => $component->display_type,
            'id' => $component->product_id,
            'name' => $component->product ? $component->product->product_name : null,
            'internal_reference' => $component->product ? $component->product->internal_reference : null,
            'description' => $component->description,
            'qty' => $component->qty,
            'unit_price' => $component->unit_price,
            'tax' => $component->tax,
            'subtotal' => $component->subtotal,
            'qty_received' => $component->qty_received,
            'qty_to_invoice' => $component->qty_to_invoice,
            'qty_invoiced' => $component->qty_invoiced,
        ];
    }
}
