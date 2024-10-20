<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Image;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Number;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category', 'tag')->orderBy('created_at', 'ASC')->get();
        $productData = $products->map(function ($product) {
            return [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'category_id' => $product->category_id,
                'category_name' => $product->category->category,
                // 'sales_price' => number_format($product->sales_price, 2),
                // 'cost' => number_format($product->cost, 2),
                'sales_price' => $product->sales_price,
                'cost' => $product->cost,
                'barcode' => $product->barcode,
                'internal_reference' => $product->internal_reference,
                'tags' => $product->tag->map(function ($tag) {
                    return [
                        'id' => $tag->tag_id,
                        'name' => $tag->name_tag
                    ];
                }),
                'notes' => $product->notes,
                'image_uuid' => $product->image_uuid,
                'image_url' => $product->image_url,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
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
            'image_uuid' => 'required|string|exists:images,image_uuid',
        ], [
            'product_name.required' => 'Product Name Must Be Filled',
            'category_id.required' => 'Category Must Be Filled',
            'sales_price.required' => 'Sales Price Must Be Filled',
            'cost.required' => 'Cost Must Be Filled',
            'image_uuid.required' => 'Image Must Be Filled',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();
        $validator = $this->validateProduct($request);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = Image::where('image_uuid', $data['image_uuid'])->first();
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found'
            ], 404);
        }

        $imageUrl = url('/storage/images/' . $image->image);

        $product = Product::create([
            'product_name' => $data['product_name'],
            'category_id' => $data['category_id'],
            'sales_price' => $data['sales_price'],
            'cost' => $data['cost'],
            'barcode' => $data['barcode'],
            'internal_reference' => $data['internal_reference'],
            'notes' => $data['notes'],
            'image_uuid' => $image->image_uuid,
            'image_url' => $imageUrl,
        ]);

        $product->tag()->sync($data['tags']);

        $productWithTag = Product::with('tag')->find($product->product_id);

        return new ProductResource(true, 'Product Data Successfully Added', [
            'product_id' => $productWithTag->product_id,
            'product_name' => $productWithTag->product_name,
            'category_id' => $productWithTag->category_id,
            // 'sales_price' => number_format($productWithTag->sales_price, 2),
            // 'cost' => number_format($productWithTag->cost, 2),
            'sales_price' => $product->sales_price,
            'cost' => $product->cost,
            'barcode' => $productWithTag->barcode,
            'internal_reference' => $productWithTag->internal_reference,
            'notes' => $productWithTag->notes,
            'tags' => $productWithTag->tag->map(function ($tag) {
                return [
                    'id' => $tag->tag_id,
                    'name' => $tag->name_tag,
                ];
            }),
            'image_uuid' => $productWithTag->image_uuid,
            'image_url' => $productWithTag->image_url,
        ]);
    }

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
            'product_id' => $product->product_id,
            'product_name' => $product->product_name,
            'category_id' => $product->category_id,
            // 'sales_price' => number_format($product->sales_price, 2),
            // 'cost' => number_format($product->cost, 2),
            'sales_price' => $product->sales_price,
            'cost' => $product->cost,
            'barcode' => $product->barcode,
            'internal_reference' => $product->internal_reference,
            'notes' => $product->notes,
            'tags' => $product->tag->map(function ($tag) {
                return [
                    'id' => $tag->tag_id,
                    'name' => $tag->name_tag,
                ];
            }),
            'image_uuid' => $product->image_uuid,
            'image_url' => $product->image_url,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->json()->all();
        $validator = $this->validateProduct($request);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $imageUuid = $data['image_uuid'] ?? $product->image_uuid;
        // $image = Image::where('image_uuid', $imageUuid)->first();
        // if ($image) {
        //     $imageUrl = url('/storage/images/' . $image->image);
        // } else {
        //     $imageUrl = $product->image_url;
        // }

        $product->update([
            'product_name' => $data['product_name'],
            'category_id' => $data['category_id'],
            'sales_price' => $data['sales_price'],
            'cost' => $data['cost'],
            'barcode' => $data['barcode'],
            'internal_reference' => $data['internal_reference'],
            'notes' => $data['notes'],
            'image_uuid' => $imageUuid,
            'image_url' => $data['image_url'],
        ]);

        $product->tag()->sync($data['tags']);

        $productWithTag = Product::with('tag')->find($product->product_id);

        return new ProductResource(true, 'Product Data Successfully Updated', [
            'product_id' => $productWithTag->product_id,
            'product_name' => $productWithTag->product_name,
            'category_id' => $productWithTag->category_id,
            // 'sales_price' => number_format($productWithTag->sales_price, 2),
            // 'cost' => number_format($productWithTag->cost, 2),
            'sales_price' => $product->sales_price,
            'cost' => $product->cost,
            'barcode' => $productWithTag->barcode,
            'internal_reference' => $productWithTag->internal_reference,
            'notes' => $productWithTag->notes,
            'tags' => $productWithTag->tag->map(function ($tag) {
                return [
                    'id' => $tag->tag_id,
                    'name' => $tag->name_tag,
                ];
            }),
            'image_uuid' => $productWithTag->image_uuid,
            'image_url' => $productWithTag->image_url,
        ]);
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
