<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
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
        $products = Product::with('category')->latest()->get();
        return new ProductResource(true, 'List Product Data', $products);
    }

    private function validateProduct(Request $request)
    {
        return Validator::make($request->all(), [
            'product_name' => 'required|string',
            'category_id' => 'required',
            'sales_price' => 'required|numeric',
            'cost' => 'required|numeric',
            'barcode' => 'required',
            'image' => $request->isMethod('post') ? 'required|image|mimes:jpeg,png,jpg|max:2048' : 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'product_name.required' => 'Product Name Must Be Filled',
            'category_id.required' => 'Category Must Be Filled',
            'sales_price.required' => 'Sales Price Must Be Filled',
            'cost.required' => 'Cost Must Be Filled',
            'barcode.required' => 'Barcode Must Be Filled',
            'image.required' => 'Image Must Be Filled',
            'image.image' => 'File Must Be An Image',
            'image.mimes' => 'Images Must Be In jpeg, png, or jpg Format',
            'image.max' => 'Maximum Image Size is 2MB',
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

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Vailed',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = $request->file('image');
        $imageName = $image->hashName();

        // Menyimpan gambar di public storage
        $image->storeAs('public/products', $imageName);

        $product = Product::create([
            'product_name' => $request->product_name,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'product_tag' => $request->product_tag,
            'notes' => $request->notes,
            'image' => $imageName,
        ]);
        return new ProductResource(true, 'Product Data Successfully Added', $product);
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
        return new ProductResource(true, 'Detail Product Data', $product);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->validateProduct($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Vailed ',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::find($id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('public/products', $image->hashName());
            Storage::delete('public/products/' . basename($product->image));


            $product->update([
                'product_name' => $request->product_name,
                'category_id' => $request->category_id,
                'sales_price' => $request->sales_price,
                'cost' => $request->cost,
                'barcode' => $request->barcode,
                'internal_reference' => $request->internal_reference,
                'product_tag' => $request->product_tag,
                'notes' => $request->notes,
                'image' => $image->hashName(),
            ]);
        } else {
            $product->update([
                'product_name' => $request->product_name,
                'category_id' => $request->category_id,
                'sales_price' => $request->sales_price,
                'cost' => $request->cost,
                'barcode' => $request->barcode,
                'internal_reference' => $request->internal_reference,
                'product_tag' => $request->product_tag,
                'notes' => $request->notes,
            ]);
        }
        return new ProductResource(true, 'Product Data Successfully Changed', $product);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        Storage::delete('public/products/' . basename($product->image));
        $product->delete();
        return new ProductResource(true, 'Data Deleted Successfully', $product);
    }
}
