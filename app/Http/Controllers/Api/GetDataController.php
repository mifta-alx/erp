<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\Category;
use App\Models\Material;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $includeProducts = $request->has('products');
        $includeMaterials = $request->has('materials');
        $includeCategories = $request->has('categories');
        $includeTags = $request->has('tags');
        $includeBoms = $request->has('boms');
        $includeVendors = $request->has('vendors');

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
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ];
        });

        $materials = Material::with('category', 'tag')->orderBy('created_at', 'ASC')->get();
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
                'tags' => $material->tag->map(function ($tag) {
                    return [
                        'id' => $tag->tag_id,
                        'name' => $tag->name_tag
                    ];
                }),
                'notes' => $material->notes,
                'image_uuid' => $material->image_uuid,
                'image_url' => $material->image_url,
                'stock' => $material->stock,
                'created_at' => $material->created_at,
                'updated_at' => $material->updated_at
            ];
        });

        $tags = Tag::orderBy('tag_id', 'ASC')->get();
        $formattedTags = $tags->map(function ($tag) {
            return [
                'id' => $tag->tag_id,
                'name' => $tag->name_tag,
            ];
        });
        
        //boms
        $boms = Bom::with(['product', 'bom_components.material'])->get();
        $bomData = $boms->map(function ($bom) {
            $bom_components = $bom->bom_components->map(function ($component) {
                $material = $component->material;
                $material_total_cost = $material->cost * $component->material_qty;
                return [
                    'material' => [
                        'id' => $material->material_id,
                        'name' => $material->material_name,
                        'cost' => $material->cost,
                        'sales_price' => $material->sales_price,
                        'barcode' => $material->barcode,
                        'internal_reference' => $material->internal_reference,
                    ],
                    'material_qty' => $component->material_qty,
                    'material_total_cost' => $material_total_cost,
                ];
            });

            $product = [
                'id' => $bom->product->product_id,
                'name' => $bom->product->product_name,
                'cost' => $bom->product->cost,
                'sales_price' => $bom->product->sales_price,
                'barcode' => $bom->product->barcode,
                'internal_reference' => $bom->product->internal_reference,
            ];

            $bom_cost = $bom_components->sum('material_total_cost');

            return [
                'bom_id' => $bom->bom_id,
                'product' => $product,
                'bom_reference' => $bom->bom_reference,
                'bom_qty' => $bom->bom_qty,
                'bom_components' => $bom_components,
                'bom_cost' => $bom_cost,
            ];
        });

        $vendors = Vendor::orderBy('created_at', 'ASC')->get();
        $vendorData = $vendors->map(function ($vendor) {
            return [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'vendor_type' => $vendor->vendor_type,
                'vendor_street' => $vendor->street,
                'vendor_city' => $vendor->city,
                'vendor_state' => $vendor->state,
                'vendor_zip' => $vendor->zip,
                'vendor_phone' => $vendor->phone,
                'vendor_mobile' => $vendor->mobile,
                'vendor_email' => $vendor->email,
                'image_uuid' => $vendor->image_uuid,
                'image_url' => $vendor->image_url,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at
            ];
        });

        $response = [
            'success' => true,
            'message' => 'Data fetched successfully',
            'data' => []
        ];

        if ($includeProducts) {
            $response['data']['products'] = $productData;
        }

        if ($includeMaterials) {
            $response['data']['materials'] = $materialData;
        }

        if ($includeCategories) {
            $response['data']['categories'] = Category::all();
        }

        if ($includeTags) {
            $response['data']['tags'] = $formattedTags;
        }

        if ($includeBoms) {
            $response['data']['boms'] = $bomData;
        }

        if ($includeVendors) {
            $response['data']['vendors'] = $vendorData;
        }

        return response()->json($response);
    }
}
