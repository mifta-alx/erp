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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $boms = Bom::with(['product', 'bom_components.material'])->get();
        $data = $boms->map(function ($bom) {
            $bom_components = $bom->bom_components->map(function ($component) {
                $material_cost = $component->material->cost;
                $material_total_cost = $material_cost * $component->material_qty;
                return [
                    'material_name' => $component->material->material_name,
                    'material_qty' => $component->material_qty,
                    'material_cost' => $material_cost,
                    'material_total_cost' => $material_total_cost,
                ];
            });
            $bom_cost = $bom_components->sum('material_total_cost');
            return [
                'bom_id' => $bom->bom_id,
                'product_name' => $bom->product->product_name,
                'product_qty' => $bom->product_qty,
                'product_cost' => $bom->product->cost,
                'bom_components' => $bom_components,
                'bom_cost' => $bom_cost,
            ];
        });

        // Return data yang sudah diformat
        return response()->json([
            'success'   => true,
            'message'   => 'Data BOM berhasil diambil',
            'data'      => $data
        ], 200);
    }

    private function validateBOMData($request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,product_id',
            'product_qty' => 'required|numeric|min:1',
            'bom_components' => 'required|array',
            'bom_components.*.material_id' => 'required|exists:materials,material_id',
            'bom_components.*.material_qty' => 'required|numeric|min:1',
        ], [
            'product_id.required' => 'Product ID is required',
            'product_id.exists' => 'Product ID not found',
            'product_qty.required' => 'Product quantity is required',
            'product_qty.numeric' => 'Product quantity must be a number',
            'product_qty.min' => 'Product quantity must be at least 1',
            'bom_components.required' => 'BOM components are required',
            'bom_components.array' => 'BOM components must be an array',
            'bom_components.*.material_id.required' => 'Material ID is required',
            'bom_components.*.material_id.exists' => 'Material ID not found',
            'bom_components.*.material_qty.required' => 'Material quantity is required',
            'bom_components.*.material_qty.numeric' => 'Material quantity must be a number',
            'bom_components.*.material_qty.min' => 'Material quantity must be at least 1',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }
        return ['data' => $validator->validated()];
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate the request data using a separate function
            $validated = $this->validateBOMData($request);

            // Check if validation fails
            if (isset($validated['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validated['errors'],
                ], 422);
            }

            // Save the BOM data
            $bom = Bom::create([
                'product_id' => $validated['data']['product_id'],
                'product_qty' => $validated['data']['product_qty'],
            ]);

            // Save each bom_component
            foreach ($validated['data']['bom_components'] as $component) {
                BomsComponent::create([
                    'bom_id' => $bom->bom_id, // Use the newly created bom_id
                    'material_id' => $component['material_id'],
                    'material_qty' => $component['material_qty'],
                ]);
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BOM data successfully saved',
                'data' => $bom->load('bom_components.material'),
            ], 201);
        } catch (\Exception $e) {
            // Rollback the transaction in case of errors
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save BOM data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
