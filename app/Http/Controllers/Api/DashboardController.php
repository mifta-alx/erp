<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ManufacturingOrder;
use App\Models\Material;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\RegisterPayment;
use App\Models\Rfq;
use App\Models\Sales;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $product = Product::count();
        $material = Material::count();
        $bom = Bom::count();
        $manufacturing = ManufacturingOrder::count();
        $vendor = Vendor::count();
        $rfq = Rfq::count();
        $sales = Sales::get();
        $totalOrder = $sales->where('state', 3)->count('sales_id');
        $totalQuotation = $sales->where('state', '<', 3)->count('sales_id');
        $totalSales = $sales->count('sales_id');
        $formatSales = [
            'total_data' => $totalSales,
            'total_income' => $sales->sum('total'),
            'total_order' => $totalOrder,
            'precentage_order' => round(($totalOrder / $totalSales) * 100, 1),
            'total_quotation' => $totalQuotation,
            'precentage_quotation' => round(($totalQuotation / $totalSales) * 100, 1),
        ];
        $customer = Customer::count();
        $customersBuy = Customer::select(
            'customers.customer_id',
            'customers.type',
            'customers.name',
            'customers.company',
            'customers.image_url',
            'customers.name',
            DB::raw('SUM(sales_components.qty) as total_products'),
            DB::raw('COUNT(DISTINCT sales.sales_id) as purchase_frequency')
        )
            ->join('sales', 'customers.customer_id', '=', 'sales.customer_id')
            ->join('sales_components', 'sales.sales_id', '=', 'sales_components.sales_id')
            ->groupBy('customers.customer_id', 'customers.name', 'customers.company', 'customers.type', 'customers.image_url')
            ->orderByDesc('total_products')
            ->get();
        $formatCustomerBuy = $customersBuy->map(function ($customer) use ($sales) {
            $totalPurchase = $sales->where('customer_id', $customer->customer_id)->sum('total');
            $companyName = null;
            if ($customer->type == 1) {
                $customerCompany = Customer::where('customer_id', $customer->company)->first();
                $companyName = $customerCompany ? $customerCompany->name : null;
            }
            return [
                'id' => $customer->customer_id,
                'name' => $customer->name,
                'company_name' => $companyName,
                'total_purchases' => $totalPurchase,
                'total_products' => $customer->total_products,
                'purchase_frequency' => $customer->purchase_frequency,
                'image_url' => $customer->image_url,
            ];
        });
        $receipt = Receipt::count();
        $invoice = Invoice::count();
        $currentMonthPayments = RegisterPayment::whereYear('payment_date', date('Y'))
            ->whereMonth('payment_date', date('m'))
            ->orderBy('payment_date', 'DESC')
            ->get(['payment_date', 'payment_type', 'amount', 'journal']);

        $paymentData = $currentMonthPayments->map(function ($payment) {
            return [
                'payment_date' => $payment->payment_date,
                'payment_type' => $payment->payment_type,
                'journal' => $payment->journal,
                'amount' => $payment->amount,
            ];
        });
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
                'mo' => [
                    'total' => $manufacturing,
                ],
                'vendor' => [
                    'total' => $vendor,
                ],
                'rfq' => [
                    'total' => $rfq,
                ],
                'receipt' => [
                    'total' => $receipt,
                ],
                'invoice' => [
                    'total' => $invoice,
                ],
                'customer' => [
                    'total' => $customer,
                ],
                'customers' => $formatCustomerBuy,
                'sales' => $formatSales,
                'payments' => $paymentData,
            ]
        ]);
    }
}
