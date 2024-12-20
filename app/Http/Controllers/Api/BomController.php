<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\BomsComponent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $product_id = $request->input('product_id');
        $query = Bom::with(['product', 'bom_components.material']);
        if ($product_id) {
            $query->where('product_id', $product_id);
        }
        $boms = $query->orderBy('created_at', 'DESC')->get();
        $data = $boms->map(function ($bom) {
            $bom_components = $bom->bom_components->map(function ($component) {
                $material = $component->material;
                $material_total_cost = $material->cost * $component->material_qty;
                return [
                    'material' => [
                        'id' => $material->material_id,
                        'name' => $material->material_name,
                        'cost' => $material->cost,
                        'sales_price' => $material->sales_price,
                        'barcode' => $material->barcode,
                        'internal_reference' => $material->internal_reference,
                    ],
                    'material_qty' => $component->material_qty,
                    'material_total_cost' => $material_total_cost,
                ];
            });

            $product = [
                'id' => $bom->product->product_id,
                'name' => $bom->product->product_name,
                'cost' => $bom->product->cost,
                'sales_price' => $bom->product->sales_price,
                'barcode' => $bom->product->barcode,
                'internal_reference' => $bom->product->internal_reference,
            ];

            $bom_cost = $bom_components->sum('material_total_cost');

            return [
                'bom_id' => $bom->bom_id,
                'product' => $product,
                'bom_reference' => $bom->bom_reference,
                'bom_qty' => $bom->bom_qty,
                'bom_components' => $bom_components,
                'bom_cost' => $bom_cost,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data BoM berhasil diambil',
            'data' => $data
        ], 200);
    }

    private function validateBOMData($request)
    {
        $rules = [
            'product_id' => 'required|exists:products,product_id',
            'bom_qty' => 'required|numeric|min:1',
        ];

        if ($request->has('bom_components') && count($request->bom_components) > 0) {
            $rules['bom_components'] = 'array';
            $rules['bom_components.*.material_id'] = 'required|exists:materials,material_id';
            $rules['bom_components.*.material_qty'] = 'required|numeric';
        }

        // Jalankan validasi
        $validator = Validator::make($request->all(), $rules, [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Product not found',
            'bom_qty.required' => 'Quantity is required',
            'bom_qty.numeric' => 'Quantity must be a number',
            'bom_qty.min' => 'Quantity must be at positive',
            'bom_components.array' => 'BOM components must be an array',
            'bom_components.*.material_id.required' => 'Material is required',
            'bom_components.*.material_id.exists' => 'Material not found',
            'bom_components.*.material_qty.required' => 'Material quantity is required',
            'bom_components.*.material_qty.numeric' => 'Material quantity must be a number',
            // 'bom_components.*.material_qty.min' => 'Material quantity must be at least 1',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }


        return ['data' => $validator->validated()];
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $this->validateBOMData($request);
            if (isset($validated['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validated['errors'],
                ], 422);
            }
            $bom = Bom::create([
                'product_id' => $validated['data']['product_id'],
                'bom_reference' => $request->input('bom_reference', null),
                'bom_qty' => $validated['data']['bom_qty'],
            ]);
            if (isset($validated['data']['bom_components']) && count($validated['data']['bom_components']) > 0) {
                foreach ($validated['data']['bom_components'] as $component) {
                    BomsComponent::create([
                        'bom_id' => $bom->bom_id,
                        'material_id' => $component['material_id'],
                        'material_qty' => $component['material_qty'],
                    ]);
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'BoM successfully added',
                'data' => [
                    'bom_id' => $bom->bom_id,
                    'product' => [
                        'id' => $bom->product->product_id,
                        'name' => $bom->product->product_name,
                        'cost' => $bom->product->cost,
                        'sales_price' => $bom->product->sales_price,
                        'barcode' => $bom->product->barcode,
                        'internal_reference' => $bom->product->internal_reference,
                    ],
                    'bom_reference' => $bom->bom_reference,
                    'bom_qty' => $bom->bom_qty,
                    'bom_components' => $bom->bom_components->isEmpty()
                        ? ['message' => 'Material are required']
                        : $bom->bom_components->map(function ($component) {
                            return [
                                'material' => [
                                    'id' => $component->material->material_id,
                                    'name' => $component->material->material_name,
                                    'cost' => $component->material->cost,
                                    'sales_price' => $component->material->sales_price,
                                    'barcode' => $component->material->barcode,
                                    'internal_reference' => $component->material->internal_reference,
                                ],
                                'material_qty' => $component->material_qty,
                                'material_total_cost' => $component->material->cost * $component->material_qty,
                            ];
                        }),
                    'bom_cost' => $bom->bom_components->sum(function ($component) {
                        return $component->material->cost * $component->material_qty;
                    }),
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to added BoM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $bom = Bom::with('bom_components.material')->find($id);
        if (!$bom) {
            return response()->json([
                'success' => false,
                'message' => 'BoM not found',
            ], 404);
        }
        $bom_components = $bom->bom_components->map(function ($component) {
            $material = $component->material;
            $material_total_cost = $material->cost * $component->material_qty;
            return [
                'material' => [
                    'id' => $material->material_id,
                    'name' => $material->material_name,
                    'cost' => $material->cost,
                    'sales_price' => $material->sales_price,
                    'barcode' => $material->barcode,
                    'internal_reference' => $material->internal_reference,
                ],
                'material_qty' => $component->material_qty,
                'material_total_cost' => $material_total_cost,
            ];
        });
        $bom_cost = $bom_components->sum('material_total_cost');
        return response()->json([
            'success' => true,
            'data' => [
                'bom_id' => $bom->bom_id,
                'product' => [
                    'id' => $bom->product->product_id,
                    'name' => $bom->product->product_name,
                    'cost' => $bom->product->cost,
                    'sales_price' => $bom->product->sales_price,
                    'barcode' => $bom->product->barcode,
                    'internal_reference' => $bom->product->internal_reference,
                ],
                'bom_reference' => $bom->bom_reference,
                'bom_qty' => $bom->bom_qty,
                'bom_components' => $bom_components,
                'bom_cost' => $bom_cost,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validated = $this->validateBOMData($request);
            if (isset($validated['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validated['errors'],
                ], 422);
            }
            $bom = Bom::find($id);
            if (!$bom) {
                return response()->json([
                    'success' => false,
                    'message' => 'BoM not found',
                ], 404);
            }
            $bom->update([
                'product_id' => $validated['data']['product_id'],
                'bom_reference' => $request->input('bom_reference', null),
                'bom_qty' => $validated['data']['bom_qty'],
            ]);
            $bom->bom_components()->delete();
            if (isset($validated['data']['bom_components']) && count($validated['data']['bom_components']) > 0) {
                foreach ($validated['data']['bom_components'] as $component) {
                    BomsComponent::create([
                        'bom_id' => $bom->bom_id,
                        'material_id' => $component['material_id'],
                        'material_qty' => $component['material_qty'],
                    ]);
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'BoM successfully updated',
                'data' => [
                    'bom_id' => $bom->bom_id,
                    'product' => [
                        'id' => $bom->product->product_id,
                        'name' => $bom->product->product_name,
                        'cost' => $bom->product->cost,
                        'sales_price' => $bom->product->sales_price,
                        'barcode' => $bom->product->barcode,
                        'internal_reference' => $bom->product->internal_reference,
                    ],
                    'bom_reference' => $bom->bom_reference,
                    'bom_qty' => $bom->bom_qty,
                    'bom_components' => $bom->bom_components->isEmpty()
                        ? ['message' => 'Data kosong']
                        : $bom->bom_components->map(function ($component) {
                            return [
                                'material' => [
                                    'id' => $component->material->material_id,
                                    'name' => $component->material->material_name,
                                    'cost' => $component->material->cost,
                                    'sales_price' => $component->material->sales_price,
                                    'barcode' => $component->material->barcode,
                                    'internal_reference' => $component->material->internal_reference,
                                ],
                                'material_qty' => $component->material_qty,
                                'material_total_cost' => $component->material->cost * $component->material_qty,
                            ];
                        }),
                    'bom_cost' => $bom->bom_components->sum(function ($component) {
                        return $component->material->cost * $component->material_qty;
                    }),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update BoM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $bom = Bom::find($id);
            if (!$bom) {
                return response()->json([
                    'success' => false,
                    'message' => 'BoM not found',
                ], 404);
            }
            $bom->bom_components()->delete();
            $bom->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BoM successfully deleted',
            ]);
        } catch (\Exception $e) {
            if ($e->getCode() == '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'BoM is in use, cannot be deleted!'
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete BoM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
