<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\ManufacturingOrder;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $product = Product::count();
        $material = Material::count();
        $bom = Bom::count();
        $manufacturing = ManufacturingOrder::count();
        return response()->json([
            'sucsess' => true,
            'message' => 'Total Data',
            'data' => [
                'products' => [
                    'total' => $product,
                ],
                'materials' => [
                    'total' => $material,
                ],
                'bom' => [
                    'total' => $bom,
                ],
                'mo' =>[
                    'total' => $manufacturing,
                ]
            ]
        ]);
    }
}
