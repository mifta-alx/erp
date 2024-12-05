<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Rfq;
use App\Models\RfqComponent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RfqController extends Controller
{
    public function index(Request $request)
    {
        $query = Rfq::query();
        if ($request->has('purchase_order') && $request->purchase_order == 'true') {
            $query->where('state', 3);
        }
        $rfq = $query->orderBy('created_at', 'DESC')->get();
        return response()->json([
            'success' => true,
            'message' => 'List RFQ Data',
            'data' => $rfq->map(function ($item) {
                return [
                    'id' => $item->rfq_id,
                    'reference' =>  $item->reference,
                    'vendor_id' => $item->vendor_id,
                    'vendor_name' => $item->vendor->name,
                    'vendor_reference' => $item->vendor_reference,
                    'order_date' => $item->order_date,
                    'state' => $item->state,
                    'taxes' => $item->taxes,
                    'total' => $item->total,
                    'confirmation_date' => $item->confirmation_date,
                    'invoice_status' => $item->invoice_status,
                    'receipt' => $item->receipts->map(function ($receipt) {
                        return [
                            'id' => $receipt->receipt_id,
                        ];
                    }),
                    'items' => $item->rfqComponent->map(function ($component) {
                        return [
                            'rfq_component_id' => $component->rfq_component_id,
                            'type' => $component->display_type,
                            'id' => $component->material_id,
                            'internal_reference' => $component->material->internal_reference ?? null,
                            'name' => $component->material->material_name ?? null,
                            'description' => $component->description,
                            'qty' => $component->qty,
                            'unit_price' => $component->unit_price,
                            'tax' => $component->tax,
                            'subtotal' => $component->subtotal,
                            'qty_received' => $component->qty_received,
                            'qty_to_invoice' =>  $component->qty_to_invoice,
                            'qty_invoiced' =>  $component->qty_invoiced,
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function show($id)
    {
        $rfq = Rfq::find($id);

        if (!$rfq) {
            return response()->json([
                'success' => false,
                'message' => 'RFQ not found',
            ], 404);
        }
        return $this->successResponse($rfq, 'Detail RFQ Data ');
    }

    private function validateRfq(Request $request)
    {
        return Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,vendor_id',
        ], [
            'vendor_id.required' => 'Vendor ID must be filled',
            'vendor_id.exists' => 'Vendor ID does not exist',
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->json()->all();
            $validator = $this->validateRfq($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lastOrder = Rfq::orderBy('created_at', 'desc')->first();
            if ($lastOrder && $lastOrder->reference) {
                $lastReferenceNumber = (int) substr($lastOrder->reference, 3);
            } else {
                $lastReferenceNumber = 0;
            }
            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
            $reference = "P{$referenceNumberPadded}";

            $orderDate = Carbon::parse($data['order_date'])->toIso8601String();
            $confirmDate = isset($data['confirmation_date']) ? Carbon::parse($data['confirmation_date'])->toIso8601String() : null;
            $rfq = Rfq::create([
                'vendor_id' => $data['vendor_id'],
                'reference' => $reference,
                'vendor_reference' => $data['vendor_reference'],
                'order_date' => $orderDate,
                'confirmation_date' => $confirmDate,
                'state' => $data['state'],
                'taxes' => $data['taxes'],
                'total' => $data['total'],
                'invoice_status' => $data['invoice_status'],
            ]);

            foreach ($data['items'] as $component) {
                if ($component['type'] == 'material') {
                    RfqComponent::create([
                        'rfq_id' => $rfq->rfq_id,
                        'display_type' => $component['type'],
                        'material_id' => $component['material_id'],
                        'description' => $component['description'],
                        'qty' => $component['qty'],
                        'unit_price' => $component['unit_price'],
                        'tax' => $component['tax'],
                        'subtotal' => $component['subtotal'],
                        'qty_received' => $component['qty_received'] ?? 0,
                        'qty_to_invoice' => $component['qty_to_invoice'] ?? 0,
                        'qty_invoiced' => $component['qty_invoiced'] ?? 0,
                    ]);
                } else {
                    RfqComponent::create([
                        'rfq_id' => $rfq->rfq_id,
                        'display_type' => $component['type'],
                        'material_id' => null,
                        'description' => $component['description'],
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
            return $this->successResponse($rfq, 'RFQ Successfully Added');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RFQ Creation Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create RFQ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->json()->all();
            $validator = $this->validateRfq($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $rfq = Rfq::find($id);
            if (!$rfq) {
                return response()->json([
                    'success' => false,
                    'message' => 'RFQ not found'
                ], 404);
            }
            $orderDate = Carbon::parse($data['order_date'])->toIso8601String();
            $confirmDate = isset($data['confirmation_date']) ? Carbon::parse($data['confirmation_date'])->toIso8601String() : null;
            $rfq->update([
                'vendor_id' => $data['vendor_id'],
                'vendor_reference' => $data['vendor_reference'],
                'order_date' => $orderDate,
                'confirmation_date' => $confirmDate,
                'state' => $data['state'],
                'taxes' => $data['taxes'],
                'total' => $data['total'],
                'invoice_status' => $data['invoice_status'],
            ]);
            foreach ($data['items'] as $component) {
                if (isset($component['component_id'])) {
                    $rfqComponent = RfqComponent::find($component['component_id']);
                    if ($rfqComponent && $rfqComponent->rfq_id === $rfq->rfq_id) {
                        $rfqComponent->update([
                            'display_type' => $component['type'],
                            'material_id' => $component['type'] == 'material' ? $component['material_id'] : null,
                            'description' => $component['description'],
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
                    RfqComponent::create([
                        'rfq_id' => $rfq->rfq_id,
                        'display_type' => $component['type'],
                        'material_id' => $component['type'] == 'material' ? $component['material_id'] : null,
                        'description' => $component['description'],
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
                    return $item['type'] === 'material';
                })) {
                    return $this->successResponse($rfq, 'RFQ Updated Successfully', true);
                } else {
                    $lastOrder = Receipt::where('transaction_type', 'IN')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $lastReferenceNumber = $lastOrder && $lastOrder->reference
                        ? (int) substr($lastOrder->reference, 4)
                        : 0;

                    $referenceNumber = $lastReferenceNumber + 1;
                    $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
                    $reference = "IN/{$referenceNumberPadded}";
                    $receipt = Receipt::create([
                        'transaction_type' => 'IN',
                        'reference' => $reference,
                        'vendor_id' => $rfq->vendor_id,
                        'rfq_id' => $rfq->rfq_id,
                        'source_document' => $rfq->reference,
                        'scheduled_date' => null,
                        'state' => 2,
                    ]);
                }
            } else if ($data['state'] == 4) {
                $receipts = Receipt::where('rfq_id', $rfq->rfq_id)
                    ->where('state', '!=', 4)
                    ->get();

                foreach ($receipts as $receipt) {
                    $receipt->update([
                        'state' => 4,
                    ]);
                }
            }

            DB::commit();
            return $this->successResponse($rfq, 'RFQ Updated Successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RFQ Update Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update RFQ',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // private function formatDate($date, $adjustTimezone)
    // {
    //     if (!$date) {
    //         return null;
    //     }

    //     $carbonDate = Carbon::parse($date)->timezone('UTC');
    //     if ($adjustTimezone) {
    //         $carbonDate->addHours(7);
    //     }
    //     return $carbonDate->toIso8601String();
    // }
    private function successResponse($rfq, $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $rfq->rfq_id,
                'reference' =>  $rfq->reference,
                'vendor_id' => $rfq->vendor_id,
                'vendor_name' => $rfq->vendor->name,
                'vendor_reference' => $rfq->vendor_reference,
                "order_date" => Carbon::parse($rfq->order_date)->setTimezone('+07:00')->format('Y-m-d H:i:s'),
                'state' => $rfq->state,
                'taxes' => $rfq->taxes,
                'total' => $rfq->total,
                'confirmation_date' => $rfq->confirmation_date
                    ? Carbon::parse($rfq->confirmation_date)->setTimezone('+07:00')->format('Y-m-d H:i:s')
                    : null,
                'invoice_status' => $rfq->invoice_status,
                'receipt' => $rfq->receipts->map(function ($receipt) {
                    return [
                        'id' => $receipt->receipt_id,
                    ];
                }),
                'items' => $rfq->rfqComponent->map(function ($component) {
                    return [
                        'component_id' => $component->rfq_component_id,
                        'type' => $component->display_type,
                        'id' => $component->material_id,
                        'internal_reference' => $component->material->internal_reference ?? null,
                        'name' => $component->material->material_name ?? null,
                        'description' => $component->description,
                        'qty' => $component->qty,
                        'unit_price' => $component->unit_price,
                        'tax' => $component->tax,
                        'subtotal' => $component->subtotal,
                        'qty_received' => $component->qty_received,
                        'qty_to_invoice' =>  $component->qty_to_invoice,
                        'qty_invoiced' =>  $component->qty_invoiced,
                    ];
                }),
            ]
        ]);
    }

    public function destroy($id) {}
}
