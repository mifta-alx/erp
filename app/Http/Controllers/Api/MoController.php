<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BomComponentResource;
use App\Http\Resources\MoResource;
use App\Models\Bom;
use App\Models\BomsComponent;
use App\Models\ManufacturingOrder;
use App\Models\Material;
use App\Models\MoComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MoController extends Controller
{
    public function index()
    {
        $mo = ManufacturingOrder::orderBy('mo_id', 'ASC')->get();
        return new MoResource(true, 'List Data Manufacturing Order', $mo);
    }

    public function show($id)
    {
        $mo = ManufacturingOrder::find($id);
        if (!$mo) {
            return response()->json([
                'success' => false,
                'message' => 'Manufacturing Order not found'
            ], 404);
        }
        return new MoResource(true, 'List Data Manufacturing Order', $mo);
    }

    private function validateMo(Request $request)
    {
        return Validator::make($request->all(), [
            'product_id' =>'required|exists:products,product_id',
            'reference' => 'required',
            'quantity' => 'required|numeric',
            'bom_id' =>'required|exists:boms,bom_id'
        ], [
            'product_id.required' => 'Product ID must be filled',
            'product_id.exists' => 'Product ID does not exist',
            'reference' => 'Reference must be filled',
            'quantity' => 'Quantity must be filled',
            'bom_id.required' => 'BOM ID must be filled',
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try{
            $data = $request->json()->all();
            $validator = $this->validateMo($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $manufacturing = ManufacturingOrder::create([
                'product_id' => $data['product_id'],
                'reference' => $data['reference'],
                'quantity' => $data['quantity'],
                'bom_id' => $data['bom_id']
            ]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'success' => false,
               'message' => 'Failed to create Manufacturing Order'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // 
    }

    public function destroy($id)
    {
        $mo = ManufacturingOrder::find($id);
        if (!$mo) {
            return response()->json([
                'success' => false,
                'message' => 'Manufacturing Order not found'
            ], 404);
        }
        $mo->delete();
        return new MoResource(true, 'Data Deleted Successfully', []);
    }
}
