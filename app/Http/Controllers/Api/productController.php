<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Image;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $products = Product::with('category')->orderBy('created_at', 'ASC')->get();
        $productData = $products->map(function ($product) {
            return [
                'id' => $product->product_id,
                'name' => $product->product_name,
                'category_id' => $product->category_id,
                'category_name' => $product->category->category,
                'sales_price' => $product->sales_price,
                'cost' => $product->cost,
                'barcode' => $product->barcode,
                'internal_reference' => $product->internal_reference,
                'product_tag' => $product->product_tag,
                'notes' => $product->notes,
                'image' => $product->image,
                'created_at'=>$product->created_at,
                'updated_at'=>$product->updated_at
            ];
        });

        return new ProductResource(true, 'List Product Data', $productData);
    }

    private function validateProduct(Request $request)
    {
        return Validator::make($request->all(), [
            'product_name' => 'required|string',
            'category_id' => 'required',
            'sales_price' => 'required|numeric',
            'cost' => 'required|numeric',
            'barcode' => 'required',
            'image' => 'required'
        ], [
            'product_name.required' => 'Product Name Must Be Filled',
            'category_id.required' => 'Category Must Be Filled',
            'sales_price.required' => 'Sales Price Must Be Filled',
            'cost.required' => 'Cost Must Be Filled',
            'barcode.required' => 'Barcode Must Be Filled',
            'image.required' => 'Image Must Be Filled',
        ]);
    }
    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
    {
        $validator = $this->validateProduct($request);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>'Validation Failed',
                'errors'=>$validator->errors()
            ],422);
        }
        $product = Product::create([
            'product_name' => $request->product_name,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'product_tag' => $request->product_tag,
            'notes' => $request->notes,
            'image' => $request->image,
        ]);

        return new ProductResource(true, 'Product Data Successfully Added', []);
    }
    /**
     * show
     *
     * @param  mixed $id
     * @return void
     */
    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        return new ProductResource(true, 'List Product Data', [
            'id' => $product->product_id,
            'name' => $product->product_name,
            'category_id' => $product->category_id,
            'category_name' => $product->category->category,
            'sales_price' => $product->sales_price,
            'cost' => $product->cost,
            'barcode' => $product->barcode,
            'internal_reference' => $product->internal_reference,
            'product_tag' => $product->product_tag,
            'notes' => $product->notes,
            'image' => $product->image,
            'created_at'=>$product->created_at,
            'updated_at'=>$product->updated_at
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->validateProduct($request);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>'Validation Failed',
                'errors'=>$validator->errors()
            ],422);
        }
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        $product->update([
            'product_name' => $request->product_name,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'product_tag' => $request->product_tag,
            'notes' => $request->notes,
            'image' => $request->image,
        ]);

        return new ProductResource(true, 'Product Data Successfully Changed', []);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        $product->delete();
        return new ProductResource(true, 'Data Deleted Successfully', []);
    }
}
