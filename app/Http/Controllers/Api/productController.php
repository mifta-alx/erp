<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Image;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index()
    {
        $products = Product::with('category', 'tag')->orderBy('created_at', 'ASC')->get();
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
            'barcode' => 'required',
            'image_uuid' => 'required'
        ], [
            'product_name.required' => 'Product Name Must Be Filled',
            'category_id.required' => 'Category Must Be Filled',
            'sales_price.required' => 'Sales Price Must Be Filled',
            'cost.required' => 'Cost Must Be Filled',
            'barcode.required' => 'Barcode Must Be Filled',
            'image_uuid.required' => 'Image Must Be Filled',
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->validateProduct($request);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $imageUuid = $request->image_uuid;
        $image = Image::where('image_uuid', $imageUuid)->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found'
            ], 404);
        }

        $storageUrl = env('STORAGE_URL');
        $imageUrl = $storageUrl . '/storage/images/' . $image->image;

        $product = Product::create([
            'product_name' => $request->product_name,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'notes' => $request->notes,
            'image_uuid' => $image->image_uuid,
            'image_url' => $imageUrl,
        ]);

        if ($request->has('tags') && is_array($request->tags)) {
            $tagIds = $request->tags;

            $existingTags = Tag::whereIn('tag_id', $tagIds)->pluck('tag_id')->toArray();

            if (count($existingTags) !== count($tagIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some tag IDs are invalid.'
                ], 422);
            }

            $product->tag()->sync($existingTags);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Tags must be an array of tag IDs.'
            ], 422);
        }
        $product = Product::with('tag')->find($product->id);

        return new ProductResource(true, 'Product Data Successfully Added', []);
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
            'id' => $product->product_id,
            'name' => $product->product_name,
            'category_id' => $product->category_id,
            'category_name' => $product->category->category,
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
        ]);
    }

    public function update(Request $request, $id)
    {
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

        $imageUuid = $request->image_uuid;
        if ($imageUuid) {
            $image = Image::where('image_uuid', $imageUuid)->first();
            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }
            $imageUrl = env('STORAGE_URL') . '/storage/images/' . $image->image;
        } else {
            $imageUrl = $product->image_url;
        }

        $product->update([
            'product_name' => $request->product_name,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'cost' => $request->cost,
            'barcode' => $request->barcode,
            'internal_reference' => $request->internal_reference,
            'notes' => $request->notes,
            'image_uuid' => $imageUuid,
            'image_url' => $imageUrl,
        ]);

        if ($request->has('tags') && is_array($request->tags)) {
            $tagIds = $request->tags;

            $existingTags = Tag::whereIn('tag_id', $tagIds)->pluck('tag_id')->toArray();

            if (count($existingTags) !== count($tagIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some tag IDs are invalid.'
                ], 422);
            }

            $product->tag()->sync($existingTags);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Tags must be an array of tag IDs.'
            ], 422);
        }
        $product = Product::with('tag')->find($product->id);

        return new ProductResource(true, 'Product Data Successfully Updated', []);
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
