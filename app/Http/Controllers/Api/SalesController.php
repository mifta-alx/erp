<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{

    public function index()
    {
        // Ambil data sales dan relasi customer serta salesComponents
        $sales = Sales::with(['customer', 'salesComponents'])->orderBy('created_at', 'ASC')->get();

        // Format data sales dengan transformasi
        $salesData = $sales->map(function ($sale) {
            return $this->transformSales($sale);
        });

        // Kembalikan response JSON
        return response()->json([
            'success' => true,
            'data' => $salesData,
        ]);
    }
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,customer_id',
            'quantity' => 'required|integer',
            'taxes' => 'required|numeric',
            'total' => 'required|numeric',
            'order_date' => 'required|date',
            'expiration' => 'nullable|date',
            'invoice_status' => 'required|in:0,1', 
            'state' => 'required|in:0,1',        
            'payment_trem' => 'nullable|integer',
            'reference' => 'nullable|string|max:255',
            'components' => 'nullable|array',
            'components.*.product_id' => 'required|exists:products,product_id',
            'components.*.description' => 'nullable|string',
            'components.*.display_type' => 'nullable|string',
            'components.*.qty' => 'required|integer',
            'components.*.unit_price' => 'required|numeric',
            'components.*.tax' => 'nullable|numeric',
            'components.*.subtotal' => 'required|numeric',
            'components.*.qty_received' => 'nullable|integer',
            'components.*.qty_to_invoice' => 'nullable|integer',
            'components.*.qty_invoiced' => 'nullable|integer',
            'components.*.state' => 'required|in:0,1', // Misalnya: 0 = Pending, 1 = Completed
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Simpan data sales
        $sales = Sales::create($request->only([
            'customer_id',
            'quantity',
            'taxes',
            'total',
            'order_date',
            'expiration',
            'invoice_status',
            'state',
            'payment_trem',
            'reference',
        ]));

        // Simpan komponen penjualan jika ada
        if ($request->has('components')) {
            foreach ($request->components as $component) {
                $sales->salesComponents()->create($component);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Sales created successfully',
            'data' => $this->transformSales($sales->load(['customer', 'salesComponents'])),
        ], 201);
    }

    private function transformSales($sale)
    {
        return [
            'id' => $sale->sales_id,
            'customer' => $sale->customer ? $sale->customer->name : null, // Null jika tidak ada customer
            'quantity' => $sale->quantity,
            'taxes' => $sale->taxes,
            'total' => $sale->total,
            'order_date' => $sale->order_date,
            'expiration' => $sale->expiration,
            'invoice_status' => $sale->invoice_status,
            'state' => $sale->state,
            'payment_trem' => $sale->payment_trem,
            'reference' => $sale->reference,
            'created_at' => $sale->created_at,
            'updated_at' => $sale->updated_at,
            'components' => $sale->salesComponents->map(function ($component) {
                return $this->transformSalesComponent($component);
            }), // Ambil semua komponen terkait
        ];
    }

    private function transformSalesComponent($component)
    {
        return [
            'id' => $component->sales_component_id,
            'product' => $component->product ? $component->product->product_name : null, // Null jika tidak ada product
            'description' => $component->description,
            'display_type' => $component->display_type,
            'qty' => $component->qty,
            'unit_price' => $component->unit_price,
            'tax' => $component->tax,
            'subtotal' => $component->subtotal,
            'qty_received' => $component->qty_received,
            'qty_to_invoice' => $component->qty_to_invoice,
            'qty_invoiced' => $component->qty_invoiced,
            'state' => $component->state,
        ];
    }
    
}
