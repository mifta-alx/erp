<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Receipt;
use App\Models\Rfq;
use App\Models\RfqComponent;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReceiptController extends Controller
{
    public function index()
    {
        $receipts = Receipt::orderBy('created_at', 'DESC')->get();
        return response()->json([
            'success' => true,
            'message' => 'List Recipt Data',
            'data' => $receipts->map(function ($receipt) {
                return [
                    'id' => $receipt->receipt_id,
                    'transaction_type' => $receipt->transaction_type,
                    'reference' => $receipt->reference,
                    'vendor_id' => $receipt->vendor_id,
                    'vendor_name' => $receipt->vendor->name,
                    'source_document' => $receipt->source_document,
                    'rfq_id' => $receipt->rfq_id,
                    'invoice_status' => $receipt->rfq->invoice_status,
                    'scheduled_date' => $receipt->scheduled_date,
                    'state' => $receipt->state,
                    'items' =>  $receipt->rfq->rfqComponent->filter(function ($component) {
                        return $component->display_type !== 'line_section';
                    })->map(function ($component) {
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
            }),
        ], 200);
    }


    private function successResponse($receipt, $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $receipt->receipt_id,
                'transaction_type' => $receipt->transaction_type,
                'reference' => $receipt->reference,
                'vendor_id' => $receipt->vendor_id,
                'vendor_name' => $receipt->vendor->name,
                'source_document' => $receipt->source_document,
                'rfq_id' => $receipt->rfq_id,
                'invoice_status' => $receipt->rfq->invoice_status,
                'scheduled_date' => $receipt->scheduled_date,
                'state' => $receipt->state,
                'items' =>  $receipt->rfq->rfqComponent->filter(function ($component) {
                    return $component->display_type !== 'line_section';
                })->map(function ($component) {
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
            ],
        ], 201);
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
            } else {
                $lastOrder = Receipt::where('transaction_type', 'OUT')->orderBy('created_at', 'desc')->first();
            }

            if ($lastOrder && $lastOrder->reference) {
                $lastReferenceNumber = (int) substr($lastOrder->reference, 4);
            } else {
                $lastReferenceNumber = 0;
            }
            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
            $reference = "{$data['transaction_type']}/{$referenceNumberPadded}";
            $scheduled_date = Carbon::parse($data['scheduled_date'])->timezone('UTC')->toIso8601String();

            $rfq = Rfq::findOrFail($data['rfq_id']);
            $rfqReference = $rfq->reference;
            $receipt = Receipt::create([
                'transaction_type' => $data['transaction_type'],
                'reference' => $reference,
                'rfq_id' => $rfq->rfq_id,
                'vendor_id' => $data['vendor_id'],
                'source_document' => $rfqReference,
                'scheduled_date' => $scheduled_date,
                'state' => $data['state']
            ]);
            DB::commit();
            return $this->successResponse($receipt, 'Receipt Successfully Added');
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
        return $this->successResponse($receipt, 'List Receipt Data ');
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
                    'items.*.qty_received' => 'required|lte:items.*.qty',
                ],
                [
                    'scheduled_date.required' => 'Scheduled Date must be filled',
                    'items.*.qty_received.lte' => 'Qty received must not exceed the quantity.',
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
            $scheduled_date = Carbon::parse($data['scheduled_date'])->timezone('UTC')->toIso8601String();
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
                                'qty_received' => $component['qty_received'],
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
                            if ($component['qty_received'] > $rfqComponent->qty) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Received quantity cannot exceed ordered quantity.',
                                    'component_id' => $component['component_id'],
                                ], 400);
                            }
                            $rfqComponent->update([
                                'material_id' => $component['material_id'],
                                'qty_received' => $component['qty_received'],
                                'qty_to_invoice' => $component['qty_received'],
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
                        'state' => $data['state'],
                    ]);
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
            return $this->successResponse($receipt, 'Receipt Successfully Updated');
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