<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $productcategory = ProductCategory::latest();
        return new ProductCategoryResource(true, 'List Data category', $productcategory);
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
            'category' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $productcategory = ProductCategory::create([
            'category' => $request->category,
        ]);
        return new ProductCategoryResource(true, 'Data Product Add Success', $productcategory);
    }
    /**
     * show
     *
     * @param  mixed $id
     * @return void
     */
    public function show($id)
    {
        $productcategory = ProductCategory::find($id);
        return new ProductCategoryResource(true, 'Detail Data product', $productcategory);
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'category' => 'required',
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $productcategory = ProductCategory::find($id);
        $productcategory->update([
            'category' => $request->category,
        ]);
        return new ProductCategoryResource(true, 'Data Product Berhasil Diubah!', $productcategory);
    }

    public function destroy($id)
    {
        $productcategory = ProductCategoryResource::find($id);
        $productcategory->delete();
        return new productResource(true, 'Data Succefully Delete', $productcategory);
    }
}
