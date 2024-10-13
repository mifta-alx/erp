<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BomsComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BomComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = BomsComponent::with('bom', 'material')->get();
        return response()->json([
            'success'   => true,
            'message'   => 'Data bom component berhasil diambil',
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
            'bom_id' => 'required',
            'material_id' => 'required',
            'material_qty' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $bomComponent = BomsComponent::create([
            'bom_id' => $request->bom_id,
            'material_id' => $request->material_id,
            'material_qty' => $request->material_qty,
        ]);
        return response()->json([
            'success'   => true,
            'message'   => 'Data bom component berhasil ditambahkan',
            'data'      => $bomComponent
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
