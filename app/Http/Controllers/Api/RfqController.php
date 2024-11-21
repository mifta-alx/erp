<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Models\RfqComponent;
use App\Models\RfqSection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RfqController extends Controller
{
    public function index()
    {
        $rfq = Rfq::with(['vendor', 'rfqSection.rfqComponent.material'])->orderBy('created_at', 'DESC')->get();

        return response()->json([
            'success' => true,
            'message' => 'RFQ Data List',
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
                    'items' => $item->rfqSection->map(function ($section) {
                        return [
                            'section_id' => $section->rfq_section_id,
                            'section_description' => $section->description,
                            'materials' => $section->rfqComponent->map(function ($component) {
                                return [
                                    'material_id' => $component->material_id,
                                    'material_reference' => $component->material->internal_reference,
                                    'material_name' => $component->material->material_name,
                                    'material_description' => $component->description,
                                    'qty' => $component->qty,
                                    'unit_price' => $component->unit_price,
                                    'tax' => $component->tax,
                                    'subtotal' => $component->subtotal,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function show($id)
    {
        $rfq = Rfq::with(['rfqSection', 'rfqComponent.material'])->find($id);

        if (!$rfq) {
            return response()->json([
                'success' => false,
                'message' => 'RFQ not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'RFQ Data List',
            'data' => [
                'id' => $rfq->rfq_id,
                'reference' =>  $rfq->reference,
                'vendor_id' => $rfq->vendor_id,
                'vendor_name' => $rfq->vendor->name,
                'vendor_reference' => $rfq->vendor_reference,
                'order_date' => $rfq->order_date,
                'state' => $rfq->state,
                'taxes' => $rfq->taxes,
                'total' => $rfq->total,
                'items' => $rfq->rfqSection->map(function ($section) {
                    return [
                        'section_id' => $section->rfq_section_id,
                        'section_description' => $section->description,
                        'materials' => $section->rfqComponent->map(function ($component) {
                            return [
                                'material_id' => $component->material_id,
                                'material_reference' => $component->material->internal_reference,
                                'material_name' => $component->material->material_name,
                                'material_description' => $component->description,
                                'qty' => $component->qty,
                                'unit_price' => $component->unit_price,
                                'tax' => $component->tax,
                                'subtotal' => $component->subtotal,
                            ];
                        }),
                    ];
                }),
            ]
        ]);
    }

    private function validateRfq(Request $request)
    {
        return Validator::make($request->all(), [
            'vendor_id' => 'required|exists:vendors,vendor_id',
        ], [
            'vendor_id.required' => 'Product ID must be filled',
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

            // Menangani penomoran referensi RFQ
            $lastOrder = Rfq::orderBy('created_at', 'desc')->first();
            if ($lastOrder && $lastOrder->reference) {
                $lastReferenceNumber = (int) substr($lastOrder->reference, 3);
            } else {
                $lastReferenceNumber = 0;
            }
            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
            $reference = "P{$referenceNumberPadded}";

            $orderDate = Carbon::parse($data['order_date'])->timezone('UTC')->toIso8601String();
            $rfq = Rfq::create([
                'vendor_id' => $data['vendor_id'],
                'reference' => $reference,
                'vendor_reference' => $data['vendor_reference'],
                'order_date' => $orderDate,
                'state' => $data['state'],
                'taxes' => 0,
                'total' => 0,
            ]);

            $rfqSections = $data['rfq_section'] ?? null;
            $sections = [];

            if ($rfqSections) {
                foreach ($rfqSections as $section) {
                    $rfqSection = RfqSection::create([
                        'rfq_id' => $rfq->rfq_id,
                        'description' => $section['description'] ?? null,
                    ]);
                    $sections[] = $rfqSection;
                }
            } else {
                $rfqSection = RfqSection::create([
                    'rfq_id' => $rfq->rfq_id,
                    'description' => null,
                ]);
                $sections[] = $rfqSection;
            }
            foreach ($data['rfq_components'] as $component) {
                $sectionId = $component['section_id'] ?? $sections[0]->rfq_section_id;
                RfqComponent::create([
                    'rfq_id' => $rfq->rfq_id,
                    'rfq_section_id' => $sectionId,
                    'material_id' => $component['material_id'],
                    'description' => $component['description'],
                    'qty' => $component['qty'],
                    'unit_price' => $component['unit_price'],
                    'tax' => $component['tax'],
                    'subtotal' => $component['subtotal'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'RFQ Data List',
                'data' => [
                    'id' => $rfq->rfq_id,
                    'reference' =>  $rfq->reference,
                    'vendor_id' => $rfq->vendor_id,
                    'vendor_name' => $rfq->vendor->name,
                    'vendor_reference' => $rfq->vendor_reference,
                    'order_date' => $rfq->order_date,
                    'state' => $rfq->state,
                    'taxes' => $rfq->taxes,
                    'total' => $rfq->total,
                    'items' => $rfq->rfqSection->map(function ($section) {
                        return [
                            'section_id' => $section->rfq_section_id,
                            'section_description' => $section->description,
                            'materials' => $section->rfqComponent->map(function ($component) {
                                return [
                                    'material_id' => $component->material_id,
                                    'material_reference' => $component->material->internal_reference,
                                    'material_name' => $component->material->material_name,
                                    'material_description' => $component->description,
                                    'qty' => $component->qty,
                                    'unit_price' => $component->unit_price,
                                    'tax' => $component->tax,
                                    'subtotal' => $component->subtotal,
                                ];
                            }),
                        ];
                    }),
                ]
            ]);
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

    public function destroy($id) {}
}
