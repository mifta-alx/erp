<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Sales;
use App\Models\SalesComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
        DB::beginTransaction();
        try {
            // Validasi input
            $validator = $this->validateSales($request);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }
            //   mengambil data terakhir
            $lastSales = Sales::orderBy('created_at', 'DESC')->first();
            if ($lastSales && $lastSales->reference) {
                $lastReferenceNumber = (int) substr($lastSales->reference, 3);
            } else {
                $lastReferenceNumber = 0;
            }
            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
            $reference = "S{$referenceNumberPadded}";
            $sales = Sales::create([
                'customer_id' => $request->customer_id,
                'quantity' => $request->quantity,
                'taxes' => $request->taxes,
                'total' => $request->total,
                'order_date' => $request->order_date,
                'expiration' => $request->expiration,
                'invoice_status' => $request->invoice_status,
                'state' => $request->state,
                'payment_terms' => $request->payment_terms,
                'reference' => $reference,
            ]);
            foreach ($request->components as $component) {
                if ($component['type'] == 'product') {
                    SalesComponent::create([
                        'sales_id' => $sales->sales_id,
                        'product_id' => $component['product_id'],
                        'description' => $component['description'],
                        'display_type' => $component['type'],
                        'qty' => $component['qty'],
                        'unit_price' => $component['unit_price'],
                        'tax' => $component['tax'],
                        'subtotal' => $component['subtotal'],
                        'qty_received' => $component['qty_received'],
                        'qty_to_invoice' => $component['qty_to_invoice'],
                        'qty_invoiced' => $component['qty_invoiced'],
                    ]);
                } else {
                    SalesComponent::create([
                        'sales_id' => $sales->sales_id,
                        'product_id' => null,
                        'description' => $component['description'],
                        'display_type' => $component['type'],
                        'qty' => 0,
                        'unit_price' => 0,
                        'tax' => 0,
                        'subtotal' => 0,
                        'qty_received' => 0,
                        'qty_to_invoice' => 0,
                        'qty_invoiced' => 0,
                    ]);
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sales created successfully',
                'data' => $this->transformSales($sales->load(['customer', 'salesComponents'])),
            ]);
        } catch (\Exception $e) {
            // Rollback jika ada error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
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
            $sales->update([
                'customer_id' => $request->customer_id,
                'quantity' => $request->quantity,
                'taxes' => $request->taxes,
                'total' => $request->total,
                'order_date' => $request->order_date,
                'expiration' => $request->expiration,
                'invoice_status' => $request->invoice_status,
                'state' => $request->state,
                'payment_terms' => $request->payment_terms,
            ]);


            // Tambahkan komponen baru
            foreach ($request->components as $component) {
                if (isset($component['sales_component_id'])) {
                    $salesComponent = SalesComponent::find($component['sales_component_id']);
                    if ($salesComponent && $salesComponent->sales_id == $sales->sales_id) {
                        $salesComponent->update([
                            'product_id' => $component['type'] == 'product' ? $component['product_id'] : null,
                            'description' => $component['description'],
                            'display_type' => $component['type'],
                            'qty' => $component['qty'] ?? 0,
                            'unit_price' => $component['unit_price'] ?? 0,
                            'tax' => $component['tax'] ?? 0,
                            'subtotal' => $component['subtotal'] ?? 0,
                            'qty_received' => $component['qty_received'] ?? 0,
                            'qty_to_invoice' => $component['qty_to_invoice'] ?? 0,
                            'qty_invoiced' => $component['qty_invoiced'] ?? 0,
                        ]);
                    }
                } else {

                    SalesComponent::create([
                        'sales_id' => $sales->sales_id,
                        'product_id' => $component['type'] == 'product' ? $component['product_id'] : null,
                        'description' => $component['description'],
                        'display_type' => $component['type'],
                        'qty' => $component['qty'] ?? 0,
                        'unit_price' => $component['unit_price'] ?? 0,
                        'tax' => $component['tax'] ?? 0,
                        'subtotal' => $component['subtotal'] ?? 0,
                        'qty_received' => $component['qty_received'] ?? 0,
                        'qty_to_invoice' => $component['qty_to_invoice'] ?? 0,
                        'qty_invoiced' => $component['qty_invoiced'] ?? 0,
                    ]);
                }
            }
            // Logika tambahan untuk state = 3 (misalnya, pemrosesan penerimaan barang)
            if ($request->state == 3) {
                $lastReceipt = Receipt::where('transaction_type', 'IN')->orderBy('created_at', 'desc')->first();
                $lastReferenceNumber = $lastReceipt ? (int) substr($lastReceipt->reference, 3) : 0;

                $referenceNumber = $lastReferenceNumber + 1;
                $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
                $reference = "IN{$referenceNumberPadded}";

                Receipt::create([
                    'transaction_type' => 'OUT',
                    'reference' => $reference,
                    'sales_id' => $sales->sales_id,
                    'source_document' => $sales->reference,
                    'state' => 2, // Pending
                ]);
            }

            // Logika untuk state = 4 (misalnya, pengubahan status penerimaan barang)
            if ($request->state == 4) {
                $receipts = Receipt::where('sales_id', $sales->sales_id)
                    ->where('state', '!=', 4) // Belum selesai
                    ->get();

                foreach ($receipts as $receipt) {
                    $receipt->update(['state' => 4]); // Selesai
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sales updated successfully',
                'data' => $this->transformSales($sales->load(['customer', 'salesComponents'])),
            ]);
        } catch (\Exception $e) {
            // Rollback jika ada error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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
    // Validasi input untuk update
    private function validateSales($request, $isUpdate = false)
    {
        $rules = [
            'customer_id' => 'required|exists:customers,customer_id',
            'quantity' => 'required|numeric',
            'taxes' => 'required|numeric',
            'total' => 'required|numeric',
            'order_date' => 'required|date',
            'expiration' => 'required|date',
            'invoice_status' => 'required|numeric',
            'state' => 'required|numeric',
            'payment_terms' => 'required',
            'components' => 'nullable|array',
            'components.*.type' => 'nullable|in:product,service',
            'components.*.product_id' => 'nullable:components.*.type,product|exists:products,product_id',
            'components.*.description' => 'nullable',
            'components.*.qty' => 'nullable|numeric',
            'components.*.unit_price' => 'nullable|numeric',
            'components.*.tax' => 'nullable|numeric',
            'components.*.subtotal' => 'nullable|numeric',
            'components.*.qty_received' => 'nullable|numeric',
            'components.*.qty_to_invoice' => 'nullable|numeric',
            'components.*.qty_invoiced' => 'nullable|numeric',
        ];



        return Validator::make($request->all(), $rules);
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
            'payment_terms' => $sale->payment_terms,
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
        ];
    }
}
