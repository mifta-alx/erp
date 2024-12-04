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
        $validator = $this->validateSales($request);

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
    public function update(Request $request, $id)
    {
        // Validasi input
        $validator = $this->validateSales($request, true);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Cari penjualan berdasarkan ID
        $sales = Sales::find($id);

        if (!$sales) {
            return response()->json([
                'success' => false,
                'message' => 'Sales not found',
            ], 404);
        }

        // Update data penjualan
        $sales->update($request->only([
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

        // Perbarui komponen penjualan jika ada
        if ($request->has('components')) {
            // Hapus komponen yang sudah ada sebelumnya hanya jika ada komponen baru
            $sales->salesComponents()->delete();

            // Simpan komponen baru jika ada
            foreach ($request->components as $component) {
                $sales->salesComponents()->create($component);
            }
        }

        // Kembalikan response setelah berhasil diupdate
        return response()->json([
            'success' => true,
            'message' => 'Sales updated successfully',
            'data' => $this->transformSales($sales->load(['customer', 'salesComponents'])),
        ]);
    }
    public function show($id)
    {
        // Cari penjualan berdasarkan ID
        $sales = Sales::with(['customer', 'salesComponents'])->find($id);

        if (!$sales) {
            return response()->json([
                'success' => false,
                'message' => 'Sales not found',
            ], 404);
        }

        // Transformasi data penjualan dan komponen terkait
        $salesData = $this->transformSales($sales);

        // Kembalikan response JSON dengan data penjualan yang ditemukan
        return response()->json([
            'success' => true,
            'data' => $salesData,
        ]);
    }
    // Pemisahan fungsi validasi
    private function validateSales(Request $request, $isUpdate = false)
    {
        return Validator::make($request->all(), [
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
            'components.*.product_id' => 'required_with:components|exists:products,product_id',
            'components.*.description' => 'nullable|string',
            'components.*.display_type' => 'nullable|string',
            'components.*.qty' => 'required_with:components|integer',
            'components.*.unit_price' => 'required_with:components|numeric',
            'components.*.tax' => 'nullable|numeric',
            'components.*.subtotal' => 'required_with:components|numeric',
            'components.*.qty_received' => 'nullable|integer',
            'components.*.qty_to_invoice' => 'nullable|integer',
            'components.*.qty_invoiced' => 'nullable|integer',
            'components.*.state' => 'required_with:components|in:0,1',
        ]);
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
