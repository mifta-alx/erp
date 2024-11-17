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
                'mo_id' => $order->mo_id,
                'reference' => $order->reference,
                'quantity' => $order->quantity,
                'bom_id' => $order->bom_id,
                'product' => [
                    'id' => $order->product->product_id,
                    'name' => $order->product->product_name,
                ],
                'state' => [
                    'id' => $order->state_id,
                    'name' => $order->state->name,
                ],
                'mo_components' => $order->mo ? $order->mo->unique('material_id')->map(function ($component) {
                    return [
                        'material' => [
                            'id' => $component->material->material_id,
                            'name' => $component->material->material_name,
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
            'mo_id' => $mo->mo_id,
            'reference' => $mo->reference,
            'quantity' => $mo->quantity,
            'bom_id' => $mo->bom_id,
            'product' => [
                'id' => $mo->product->product_id,
                'name' => $mo->product->product_name,
            ],
            'state' => [
                'id' => $mo->state_id,
                'name' => $mo->state->name,
            ],
            'mo_components' => $mo->mo->unique('material_id')->map(function ($component) {
                return [
                    'material' => [
                        'id' => $component->material->material_id,
                        'name' => $component->material->material_name,
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
            'reference' => 'required',
            'quantity' => 'required|numeric',
            'bom_id' => 'required|exists:boms,bom_id'
        ], [
            'product_id.required' => 'Product ID must be filled',
            'product_id.exists' => 'Product ID does not exist',
            'reference' => 'Reference must be filled',
            'quantity' => 'Quantity must be filled',
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
            $manufacturing = ManufacturingOrder::create([
                'product_id' => $data['product_id'],
                'reference' => $data['reference'],
                'quantity' => $data['quantity'],
                'bom_id' => $data['bom_id'],
                'state_id' => $data['state_id'],
            ]);
            $components = BomsComponent::where('bom_id', $data['bom_id'])->get();
            foreach ($components as $component) {
                $material = Material::find($component->material_id);
                $toConsume = $manufacturing->quantity * $component->material_qty;
                $reserved = $material->stock_material ?? 0;
                if ($reserved > $toConsume) {
                    $reserved = $toConsume;
                }
                $moComponent = MoComponent::create([
                    'mo_id' => $manufacturing->mo_id,
                    'material_id' => $component->material_id,
                    'to_consume' => $toConsume,
                    'reserved' => $reserved,
                    'consumed' => $reserved == $toConsume ? true : false
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Manufacturing Order Created Successfully',
                'data' => [
                    'mo_id' => $manufacturing->mo_id,
                    'reference' => $manufacturing->reference,
                    'quantity' => $manufacturing->quantity,
                    'bom_id' => $manufacturing->bom_id,
                    'product' => [
                        'id' => $manufacturing->product->product_id,
                        'name' => $manufacturing->product->product_name,
                    ],
                    'state' => [
                        'id' => $manufacturing->state_id,
                        'name' => $manufacturing->state->name,
                    ],
                    'mo_components' => $manufacturing->mo->map(function ($component) {
                        return [
                            'material' => [
                                'id' => $component->material->material_id,
                                'name' => $component->material->material_name,
                            ],
                            'to_consume' => $component->to_consume,
                            'reserved' => $component->reserved,
                            'consumed' => $component->consumed,
                        ];
                    }),
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
                    'state_id' => 'required|integer',
                    'bom_id' => 'required|integer',
                ],
                [
                    'state_id' => 'State ID must be a filled',
                    'bom_id' => 'required|integer',
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

            $manufacturing->update([
                'state_id' => $data['state_id'],
            ]);
            $components = BomsComponent::where('bom_id', $data['bom_id'])->get();
            foreach ($components as $component) {
                $material = Material::find($component->material_id);
                $toConsume = $manufacturing->quantity * $component->material_qty;

                $moComponent = MoComponent::where([
                    'mo_id' => $manufacturing->mo_id,
                    'material_id' => $component->material_id,
                ])->first();

                if ($manufacturing->state_id == 3 && (!$moComponent || !$moComponent->consumed)) {
                    $reserved = $toConsume;
                } else {
                    $reserved = min($material->stock_material, $toConsume);
                    if ($moComponent && $moComponent->consumed) {
                        $material->stock_material -= $reserved;
                        $material->save();
                    }
                }

                MoComponent::updateOrCreate(
                    [
                        'mo_id' => $manufacturing->mo_id,
                        'material_id' => $component->material_id,
                    ],
                    [
                        'to_consume' => $toConsume,
                        'reserved' => $reserved,
                        'consumed' => $reserved == $toConsume ? true : false,
                    ]
                );
            }

            $allConsumed = MoComponent::where('mo_id', $manufacturing->mo_id)->where('consumed', false)->doesntExist();

            if ($manufacturing->state_id == 3 && $allConsumed) {
                $product = $manufacturing->product;
                $product->stock_product += $manufacturing->quantity;
                $product->save();
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Manufacturing Order Created Successfully',
                'data' => [
                    'mo_id' => $manufacturing->mo_id,
                    'reference' => $manufacturing->reference,
                    'quantity' => $manufacturing->quantity,
                    'bom_id' => $manufacturing->bom_id,
                    'product' => [
                        'id' => $manufacturing->product->product_id,
                        'name' => $manufacturing->product->product_name,
                    ],
                    'state' => [
                        'id' => $manufacturing->state_id,
                        'name' => $manufacturing->state->name,
                    ],
                    $moComponents = $manufacturing->mo->unique('material_id')->map(function ($component) {
                        return [
                            'material' => [
                                'id' => $component->material->material_id,
                                'name' => $component->material->material_name,
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
