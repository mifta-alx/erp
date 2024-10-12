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
        $products = Product::with('productCategory')->latest()->get(); 
        return new ProductResource(true, 'List Data Product', $products);
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
            'product_name' => 'required',
            'category_id' => 'required',
            'sales_price' => 'required',
            'cost' => 'required',
            'barcode' => 'required',
            'internal_reference' => 'required',
            'product_tag' => 'required',
            'company' => 'required',
            // 'notes' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
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
            'company' => $request->company,
            'notes' => $request->notes,
            'image' => $imageName,
        ]);


        return new ProductResource(true, 'Data Product Add Success', $product);
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
        return new ProductResource(true, 'Detail Data product', $product);
    }

    public function update(Request $request,$id)
    {
       
        $validator = Validator::make($request->all(), [
            'product_name' => 'required',
            'category_id' => 'required',
            'sales_price' => 'required',
            'cost' => 'required',
            'barcode' => 'required',
            'internal_reference' => 'required',
            'product_tag' => 'required',
            'company' => 'required',
            // 'notes' => 'required',
        ]);
      

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
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
                'company' => $request->company,
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
                'company' => $request->company,
                'notes' => $request->notes,
            ]);
        }
        return new ProductResource(true, 'Data Product Berhasil Diubah!', $product);
    }

    public function destroy($id){
        $product = Product::find($id);
        Storage::delete('public/products/'. basename($product->image));
        $product->delete();
        return new ProductResource(true, 'Data Succefully Delete', $product);
    }
}
