<?php

namespace App\Http\Controllers\Api;

use App\Models\Material;
use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $material = Material::with('category')->latest()->get();
        return new MaterialResource(true, 'List Material Data', $material);
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
                'image' => $request->isMethod('post') ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            ],
            [
                'material_name.required' => 'Material Name Must Be Filled',
                'category_id.required' => 'Category Must Be Filled',
                'sales_price.required' => 'Sales Price Must Be Filled',
                'cost.required' => 'Cost Must Be Filled',
                'barcode.required' => 'Barcode Must Be Filled',
                'image.required' => 'Image Must Be Filled',
                'image.image' => 'File Must Be An Image',
                'image.mimes' => 'Images Must Be In jpeg, png, or jpg Format',
                'image.max' => 'Maximum Image Size is 2MB',
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

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Vailed',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = $request->file('image');
        $image->storeAs('public/materials', $image->hashName());

        $material = Material::create([
            'material_name' => $request->material_name,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'material_tag' => $request->material_tag,
            'notes' => $request->notes,
            'image' => $image->hashName(),
        ]);


        return new MaterialResource(true, 'Material Data Successfully Added', $material);
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
        return new MaterialResource(true, 'Detail Material Data', $material);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->validateMaterial($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $material = Material::find($id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('public/materials', $image->hashName());
            Storage::delete('public/materials/' . basename($material->image));


            $material->update([
                'material_name' => $request->material_name,
                'category_id' => $request->category_id,
                'sales_price' => $request->sales_price,
                'cost' => $request->cost,
                'barcode' => $request->barcode,
                'internal_reference' => $request->internal_reference,
                'material_tag' => $request->material_tag,
                'notes' => $request->notes,
                'image' => $image->hashName(),
            ]);
        } else {
            $material->update([
                'material_name' => $request->material_name,
                'category_id' => $request->category_id,
                'sales_price' => $request->sales_price,
                'cost' => $request->cost,
                'barcode' => $request->barcode,
                'internal_reference' => $request->internal_reference,
                'material_tag' => $request->material_tag,
                'notes' => $request->notes,
            ]);
        }
        return new MaterialResource(true, 'Material Data Successfully Changed', $material);
    }

    public function destroy($id)
    {
        $material = Material::find($id);
        Storage::delete('public/materials/' . basename($material->image));
        $material->delete();
        return new MaterialResource(true, 'Data Deleted Successfully', $material);
    }
}
