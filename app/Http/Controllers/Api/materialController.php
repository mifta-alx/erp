<?php

namespace App\Http\Controllers\Api;

use App\Models\material;
use App\Http\Controllers\Controller;
use App\Http\Resources\materialResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class materialController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $material = Material::latest()->paginate(5);
        return new materialResource(true, 'List Data material', $material);
    }
    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'material_name' => 'required',
            'sales_price' => 'required',
            'cost' => 'required',
            'barcode' => 'required',
            'internal_reference' => 'required',
            'material_tag' => 'required',
            'notes' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $image = $request->file('image');
        $image->storeAs('public/materials', $image->hashName());

        $material = Material::create([
            'material_name' => $request->material_name,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'material_tag' => $request->material_tag,
            'notes' => $request->notes,
            'image' => $image->hashName(),
        ]);


        return new materialResource(true, 'Data Material Add Success', $material);
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
        return new materialResource(true, 'Detail Data Material', $material);
    }

    public function update(Request $request,$id)
    {
       
        $validator = Validator::make($request->all(), [
            'material_name' => 'required',
            'sales_price' => 'required',
            'cost' => 'required',
            'barcode' => 'required',
            'internal_reference' => 'required',
            'material_tag' => 'required',
            'notes' => 'required',
        ]);
      

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

         $material = Material::find($id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('public/materials', $image->hashName());
            Storage::delete('public/materials/' . basename($material->image));


            $material->update([
                'material_name' => $request->material_name,
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
                'sales_price' => $request->sales_price,
                'cost' => $request->cost,
                'barcode' => $request->barcode,
                'internal_reference' => $request->internal_reference,
                'material_tag' => $request->material_tag,
                'notes' => $request->notes,
            ]);
        }
        return new materialResource(true, 'Data material Berhasil Diubah!', $material);
    }

    public function destroy($id){
        $material = material::find($id);
        Storage::delete('public/materials/'. basename($material->image));
        $material->delete();
        return new materialResource(true, 'Data Succefully Delete', $material);
    }
}
