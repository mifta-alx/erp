<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MoResource;
use App\Models\BomsComponent;
use App\Models\ManufacturingOrder;
use App\Models\Material;
use App\Models\MoComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MoController extends Controller
{
    public function index()
    {
        $mo = ManufacturingOrder::orderBy('mo_id', 'ASC')->get();
        return new MoResource(true, 'List Data Manufacturing Order', $mo->map(function ($order) {
            return [
                'id' => $order->mo_id,
                'reference' => $order->reference,
                'qty' => $order->qty,
                'bom_id' => $order->bom_id,
                'product' => [
                    'id' => $order->product->product_id,
                    'name' => $order->product->product_name,
                    'cost' => $order->product->cost,
                    'sales_price' => $order->product->sales_price,
                    'barcode' => $order->product->barcode,
                    'internal_reference' => $order->product->internal_reference,
                ],
                'state' => $order->state,
                'status' => $order->status,
                'mo_components' => $order->mo ? $order->mo->unique('material_id')->map(function ($component) {
                    return [
                        'material' => [
                            'id' => $component->material->material_id,
                            'name' => $component->material->material_name,
                            'cost' => $component->material->cost,
                            'sales_price' => $component->material->sales_price,
                            'barcode' => $component->material->barcode,
                            'internal_reference' => $component->material->internal_reference,
                        ],
                        'to_consume' => $component->to_consume,
                        'reserved' => $component->reserved,
                        'consumed' => $component->consumed,
                    ];
                }) : [],
            ];
        }));
    }


    public function show($id)
    {
        $mo = ManufacturingOrder::find($id);
        if (!$mo) {
            return response()->json([
                'success' => false,
                'message' => 'Manufacturing Order not found'
            ], 404);
        }
        return new MoResource(true, 'List Manufacturing Order Data', [
            'id' => $mo->mo_id,
            'reference' => $mo->reference,
            'qty' => $mo->qty,
            'bom_id' => $mo->bom_id,
            'product' => [
                'id' => $mo->product->product_id,
                'name' => $mo->product->product_name,
                'cost' => $mo->product->cost,
                'sales_price' => $mo->product->sales_price,
                'barcode' => $mo->product->barcode,
                'internal_reference' => $mo->product->internal_reference,
            ],
            'state' => $mo->state,
            'status' => $mo->status,
            'mo_components' => $mo->mo->unique('material_id')->map(function ($component) {
                return [
                    'material' => [
                        'id' => $component->material->material_id,
                        'name' => $component->material->material_name,
                        'cost' => $component->material->cost,
                        'sales_price' => $component->material->sales_price,
                        'barcode' => $component->material->barcode,
                        'internal_reference' => $component->material->internal_reference,
                    ],
                    'to_consume' => $component->to_consume,
                    'reserved' => $component->reserved,
                    'consumed' => $component->consumed,
                ];
            }),
        ]);
    }

    private function validateMo(Request $request)
    {
        return Validator::make($request->all(), [
            'product_id' => 'required|exists:products,product_id',
            'reference' => 'nullable',
            'qty' => 'required|numeric',
            'bom_id' => 'required|exists:boms,bom_id'
        ], [
            'product_id.required' => 'Product ID must be filled',
            'product_id.exists' => 'Product ID does not exist',
            'qty' => 'Quantity must be filled',
            'bom_id.required' => 'BOM ID must be filled',
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->json()->all();
            $validator = $this->validateMo($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lastOrder = ManufacturingOrder::orderBy('created_at', 'desc')->first();
            if ($lastOrder && $lastOrder->reference) {
                $lastReferenceNumber = (int) substr($lastOrder->reference, 3);
            } else {
                $lastReferenceNumber = 0;
            }
            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
            $reference = "MO/{$referenceNumberPadded}";

            $manufacturing = ManufacturingOrder::create([
                'product_id' => $data['product_id'],
                'reference' => $reference,
                'qty' => $data['qty'],
                'bom_id' => $data['bom_id'],
                'state' => $data['state'],
                'status' => $data['status'] ?? 'process',
            ]);

            $components = BomsComponent::where('bom_id', $data['bom_id'])->get();
            foreach ($components as $component) {
                $material = Material::find($component->material_id);
                MoComponent::create([
                    'mo_id' => $manufacturing->mo_id,
                    'material_id' => $component->material_id,
                    'to_consume' => $component->material_qty,
                    'reserved' => 0,
                    'consumed' => 0
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Manufacturing Order Created Successfully',
                'data' => [
                    'id' => $manufacturing->mo_id,
                    'reference' => $manufacturing->reference,
                    'qty' => $manufacturing->qty,
                    'bom_id' => $manufacturing->bom_id,
                    'product' => [
                        'id' => $manufacturing->product->product_id,
                        'name' => $manufacturing->product->product_name,
                        'cost' => $manufacturing->product->cost,
                        'sales_price' => $manufacturing->product->sales_price,
                        'barcode' => $manufacturing->product->barcode,
                        'internal_reference' => $manufacturing->product->internal_reference,
                    ],
                    'state' => $manufacturing->state,
                    'status' => $manufacturing->status,
                    'mo_component' => $manufacturing->mo->unique('material_id')->map(function ($component) {
                        return [
                           'material' => [
                                'id' => $component->material->material_id,
                                'name' => $component->material->material_name,
                                'cost' => $component->material->cost,
                                'sales_price' => $component->material->sales_price,
                                'barcode' => $component->material->barcode,
                                'internal_reference' => $component->material->internal_reference,
                            ],
                            'to_consume' => $component->to_consume,
                            'reserved' => $component->reserved,
                            'consumed' => $component->consumed,
                        ];
                    })
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manufacturing Order Creation Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Manufacturing Order',
                'error' => $e->getMessage()
            ], 500);
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
                    'state' => 'required|integer',
                    'bom_id' => 'required|integer|exists:boms,bom_id',
                ],
                [
                    'state.required' => 'State ID must be filled.',
                    'bom_id.required' => 'BOM ID must be filled.',
                    'bom_id.exists' => 'BoM not found.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $manufacturing = ManufacturingOrder::find($id);
            if (!$manufacturing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manufacturing Order not found'
                ], 404);
            }

            switch ($data['state']) {
                case 1:
                    $manufacturing->update([
                        'state' => $data['state'],
                    ]);
                    break;
                case 2:
                    $manufacturing->update([
                        'state' => $data['state'],
                    ]);
                    break;
                case 3:
                    $this->processState3($manufacturing, $data);
                    break;

                case 4:
                    $this->processState4($manufacturing, $data);
                    break;

                case 5:
                    $this->processState5($manufacturing, $data);
                    break;

                default:
                    $manufacturing->update([
                        'state' => $data['state'],
                    ]);
                    return $this->successResponse($manufacturing);
            }

            DB::commit();
            return $this->successResponse($manufacturing);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manufacturing Order Update Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to Update Manufacturing Order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function processState3($manufacturing, $data)
    {
        $components = BomsComponent::where('bom_id', $data['bom_id'])->get();
        $hasInsufficientStock = false;

        foreach ($components as $component) {
            $requiredQty = $component->material_qty * $data['qty'];
            $material = Material::find($component->material_id);

            if (!$material || $material->stock < $requiredQty) {
                $hasInsufficientStock = true;
                break;
            } else {
                $toConsume = $requiredQty;
                $reserved = min($material->stock, $toConsume);

                MoComponent::updateOrCreate(
                    [
                        'mo_id' => $manufacturing->mo_id,
                        'material_id' => $component->material_id,
                    ],
                    [
                        'to_consume' => $toConsume,
                        'reserved' => $reserved,
                        'consumed' => 0,
                    ]
                );
            }
        }

        $status = $hasInsufficientStock ? 'failed' : 'process';
        $manufacturing->update([
            'state' => $data['state'],
            'status' => $status
        ]);
    }

    private function processState4($manufacturing, $data)
    {
        $components = BomsComponent::where('bom_id', $data['bom_id'])->get();

        foreach ($components as $component) {
            $material = Material::find($component->material_id);
            $toConsume = $manufacturing->qty * $component->material_qty;

            $moComponent = MoComponent::where([
                'mo_id' => $manufacturing->mo_id,
                'material_id' => $component->material_id,
            ])->first();

            $consumed = ($moComponent && $moComponent->reserved >= $toConsume) ? $toConsume : $moComponent->reserved;

            MoComponent::updateOrCreate(
                [
                    'mo_id' => $manufacturing->mo_id,
                    'material_id' => $component->material_id,
                ],
                [
                    'consumed' => $consumed,
                ]
            );
        }
    }

    private function processState5($manufacturing, $data)
    {
        $manufacturing->update([
            'product_id' => $data['product_id'],
            'qty' => $data['qty'],
            'bom_id' => $data['bom_id'],
            'state' => $data['state'],
            'status' => 'success',
        ]);

        $components = BomsComponent::where('bom_id', $data['bom_id'])->get();
        foreach ($components as $component) {
            $material = Material::find($component->material_id);
            $moComponent = MoComponent::where([
                'mo_id' => $manufacturing->mo_id,
                'material_id' => $component->material_id,
            ])->first();

            if ($moComponent) {
                $toConsume = $moComponent->to_consume;
                if ($moComponent->consumed < $toConsume) {
                    $reserved = min($toConsume, $moComponent->reserved);
                    $moComponent->consumed = $reserved;
                    $moComponent->save();
                }

                if ($moComponent->consumed == $toConsume) {
                    $material->stock -= $toConsume;
                    $material->save();
                }
            }
        }

        $allConsumed = MoComponent::where('mo_id', $manufacturing->mo_id)
            ->whereColumn('consumed', '<', 'to_consume')
            ->doesntExist();

        if ($allConsumed) {
            $product = $manufacturing->product;
            $product->stock += $manufacturing->qty;
            $product->save();
        }
    }

    private function successResponse($manufacturing)
    {
        return response()->json([
            'success' => true,
            'message' => 'Manufacturing Order Successfully Updated',
            'data' => [
                'id' => $manufacturing->mo_id,
                'reference' => $manufacturing->reference,
                'qty' => $manufacturing->qty,
                'bom_id' => $manufacturing->bom_id,
                'product' => [
                    'id' => $manufacturing->product->product_id,
                    'name' => $manufacturing->product->product_name,
                    'cost' => $manufacturing->product->cost,
                    'sales_price' => $manufacturing->product->sales_price,
                    'barcode' => $manufacturing->product->barcode,
                    'internal_reference' => $manufacturing->product->internal_reference,
                ],
                'state' => $manufacturing->state,
                'status' => $manufacturing->status,
                'mo_components' => $manufacturing->mo->unique('material_id')->map(function ($component) {
                    return [
                        'material' => [
                            'id' => $component->material->material_id,
                            'name' => $component->material->material_name,
                            'cost' => $component->material->cost,
                            'sales_price' => $component->material->sales_price,
                            'barcode' => $component->material->barcode,
                            'internal_reference' => $component->material->internal_reference,
                        ],
                        'to_consume' => $component->to_consume,
                        'reserved' => $component->reserved,
                        'consumed' => $component->consumed,
                    ];
                })
            ]
        ], 201);
    }


    public function destroy($id)
    {
        $mo = ManufacturingOrder::find($id);
        if (!$mo) {
            return response()->json([
                'success' => false,
                'message' => 'Manufacturing Order not found'
            ], 404);
        }
        $mo->delete();
        return new MoResource(true, 'Data Deleted Successfully', []);
    }
}
