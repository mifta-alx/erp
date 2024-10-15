<?php

namespace App\Http\Controllers\Api;

use App\Models\Material;
use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $materials = Material::with('category')->orderBy('created_at', 'ASC')->get();
        $materialData = $materials->map(function ($material) {
            return [
                'id' => $material->material_id,
                'name' => $material->material_name,
                'category_id' => $material->category_id,
                'category_name' => $material->category->category,
                'sales_price' => $material->sales_price,
                'cost' => $material->cost,
                'barcode' => $material->barcode,
                'internal_reference' => $material->internal_reference,
                'material_tag' => $material->material_tag,
                'notes' => $material->notes,
                'image' => $material->image,
                'created_at' => $material->created_at,
                'updated_at' => $material->updated_at
            ];
        });

        return new MaterialResource(true, 'List Material Data', $materialData);
    }

    private function validateMaterial(Request $request)
    {
        return Validator::make(
            $request->all(),
            [
                'material_name' => 'required|string',
                'category_id' => 'required',
                'sales_price' => 'required|numeric',
                'cost' => 'required|numeric',
                'barcode' => 'required',
                'image' => 'required'
            ],
            [
                'material_name.required' => 'Material Name Must Be Filled',
                'category_id.required' => 'Category Must Be Filled',
                'sales_price.required' => 'Sales Price Must Be Filled',
                'cost.required' => 'Cost Must Be Filled',
                'barcode.required' => 'Barcode Must Be Filled',
                'image.required' => 'Image Must Be Filled',
            ]
        );
    }
    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
    {
        $validator = $this->validateMaterial($request);

        $material = Material::create([
            'material_name' => $request->material_name,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'material_tag' => $request->material_tag,
            'notes' => $request->notes,
            'image' => $request->image,
        ]);

        return new MaterialResource(true, 'Material Data Successfully Added', []);
    }
    /**
     * show
     *
     * @param  mixed $id
     * @return void
     */
    public function show($id)
    {
        $material = Material::find($id);
        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found'
            ], 404);
        }
        return new MaterialResource(true, 'Detail Material Data', [
            'id' => $material->material_id,
            'name' => $material->material_name,
            'category_id' => $material->category_id,
            'category_name' => $material->category->category,
            'sales_price' => $material->sales_price,
            'cost' => $material->cost,
            'barcode' => $material->barcode,
            'internal_reference' => $material->internal_reference,
            'material_tag' => $material->material_tag,
            'notes' => $material->notes,
            'image' => $material->image,
            'created_at' => $material->created_at,
            'updated_at' => $material->updated_at
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->validateMaterial($request);

        $material = Material::find($id);
        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found'
            ], 404);
        }
        $material->update([
            'material_name' => $request->material_name,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'material_tag' => $request->material_tag,
            'notes' => $request->notes,
            'image' => $request->image,
        ]);

        return new MaterialResource(true, 'Material Data Successfully Changed', []);
    }

    public function destroy($id)
    {
        $material = Material::find($id);
        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found'
            ], 404);
        }
        $material->delete();
        return new MaterialResource(true, 'Data Deleted Successfully', []);
    }
}
