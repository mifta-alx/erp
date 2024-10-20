<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\BomsComponent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                    'material_name' => $component->material->material_name,
                    'material_qty' => $component->material_qty,
                    'material_cost' => $material_cost,
                    'material_total_cost' => $material_total_cost,
                ];
            });
            $bom_cost = $bom_components->sum('material_total_cost');
            return [
                'bom_id' => $bom->bom_id,
                'product_name' => $bom->product->product_name,
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
        DB::beginTransaction();
    
        try {
            // Validasi data
            $validated = $request->validate([
                'product_id' => 'required|exists:products,product_id',
                'product_qty' => 'required|numeric|min:1',
                'bom_components' => 'required|array',
                'bom_components.*.material_id' => 'required|exists:materials,material_id',
                'bom_components.*.material_qty' => 'required|numeric|min:1',
            ]);
    
            // Simpan data BOM
            $bom = Bom::create([
                'product_id' => $validated['product_id'],
                'product_qty' => $validated['product_qty'],
            ]);
    
            // Simpan setiap bom_component
            foreach ($validated['bom_components'] as $component) {
                BomsComponent::create([
                    'bom_id' => $bom->bom_id, // Menggunakan bom_id yang baru dibuat
                    'material_id' => $component['material_id'],
                    'material_qty' => $component['material_qty'],
                ]);
            }
    
            // Commit transaksi
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Data BOM berhasil disimpan',
                'data' => $bom->load('bom_components.material'), // Load relasi untuk menampilkan hasil lengkap
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data BOM',
                'error' => $e->getMessage(),
            ], 500);
        }
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
