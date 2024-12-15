<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Image;
use App\Models\SalesComponent;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                'stock' => $product->stock,
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

    private function successResponse($productWithTag, $message)
    {
        return new ProductResource(true, $message, [
            'id' => $productWithTag->product_id,
            'name' => $productWithTag->product_name,
            'category_id' => $productWithTag->category_id,
            'category_name' => $productWithTag->category->category,
            'sales_price' => $productWithTag->sales_price,
            'cost' => $productWithTag->cost,
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
            'stock' => $productWithTag->stock,
        ]);
    }

    public function store(Request $request)
    {
        try {
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

            $imageUrl = url('images/' . $image->image);

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
                'stock' => $data['stock'] ?? 0,
            ]);

            $product->tag()->sync($data['tags']);
            $productWithTag = Product::with('tag')->find($product->product_id);
            return $this->successResponse($productWithTag, 'Product Data Successfully Added');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
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
        return $this->successResponse($product, 'List Product Data');
    }

    public function update(Request $request, $id)
    {
        try {
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
                'stock' => $data['stock'] ?? 0,
            ]);

            $product->tag()->sync($data['tags']);

            $productWithTag = Product::with('tag')->find($product->product_id);
            return $this->successResponse($productWithTag, 'Product Data Successfully Updated');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,product_id',
            'qty' => 'required|numeric',
        ]);
        $product = Product::find($request->id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found.',
            ], 404);
        }

        $reserved = min($product->stock, $request->qty);

        $salesComponents = SalesComponent::where('product_id', $request->id)
            ->where('display_type', '!=', 'line_section')
            ->get();

        $items = $salesComponents->map(function ($component) use ($reserved) {
            return [
                'component_id' => $component->sales_component_id,
                'type' => $component->display_type,
                'id' => $component->product_id,
                'internal_reference' => $component->product->internal_reference,
                'name' => $component->product->product_name,
                'description' => $component->description,
                'qty' => $component->qty,
                'unit_price' => $component->unit_price,
                'tax' => $component->tax,
                'subtotal' => $component->subtotal,
                'qty_received' => $component->qty_received,
                'qty_to_invoice' => $component->qty_to_invoice,
                'qty_invoiced' => $component->qty_invoiced,
                'reserved' => $reserved,
            ];
        });

        if ($product->stock >= $request->qty) {
            return response()->json([
                'status' => 'success',
                'message' => 'Stock is sufficient.',
                'items' => $items,
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient stock.',
                'items' => $items,
            ], 200);
        }
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

        $imageUuid = $product->image_uuid;
        $image = Image::where('image_uuid', $imageUuid)->first();
        if ($image) {
            $oldImagePath = public_path('images/' . $image->image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            $image->delete();
        }
        DB::table('images')->where('image_uuid', $imageUuid)->delete();

        $product->delete();
        return new ProductResource(true, 'Data Deleted Successfully', []);
    }
}
