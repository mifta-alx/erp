<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class BomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $boms = Bom::with(['product', 'bom_components.material'])->get(); 
    $data = $boms->map(function ($bom) {
        $bom_components = $bom->bom_components->map(function ($component) {
            $material_cost = $component->material->cost;
            $material_total_cost = $material_cost * $component->material_qty;
            return [
                'material_id' => $component->material_id,
                'material_qty' => $component->material_qty,
                'material_cost' => $material_cost, 
                'material_total_cost' => $material_total_cost,
            ];
        });
        $bom_cost = $bom_components->sum('material_total_cost');

        return [
            'bom_id' => $bom->bom_id,
            'product_id' => $bom->product_id,
            'product_qty' => $bom->product_qty,
            'product_cost' => $bom->product->cost, 
            'bom_components' => $bom_components,
            'bom_cost' => $bom_cost, 
        ];
    });

    // Return data yang sudah diformat
    return response()->json([
        'success'   => true,
        'message'   => 'Data BOM berhasil diambil',
        'data'      => $data
    ], 200);
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'product_qty' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $bom = Bom::create([
            'product_id' => $request->product_id,
            'product_qty' => $request->product_qty,
        ]);
        return response()->json([
            'success'   => true,
            'message'   => 'Data bom berhasil ditambahkan',
            'data'      => $bom
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
