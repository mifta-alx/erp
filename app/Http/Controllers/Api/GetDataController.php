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

        $response = [
            'success' => true,
            'message' => 'Data fetched successfully',
            'data' => []
        ];

        if ($includeProducts) {
            $response['data']['product'] = Product::all();
        }

        if ($includeMaterials) {
            $response['data']['material'] = Material::all();
        }

        if ($includeCategories) {
            $response['data']['category'] = Category::all();
        }

        if ($includeTags) {
            $response['data']['tag'] = Tag::all();
        }

        if ($includeBoms) {
            $response['data']['bom'] = Bom::all();
        }

        if ($includeVendors) {
            $response['data']['vendor'] = Vendor::all();
        }

        return response()->json($response);
    }
}
