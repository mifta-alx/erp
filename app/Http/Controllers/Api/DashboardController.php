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
        $product = $this->getProductCount();
        $material = $this->getMaterialCount();
        $bom = $this->getBomCount();
        $manufacturing = $this->getManufacturingCount();
        $vendor = $this->getVendorCount();
        $rfq = $this->getRfqCount();
        $formatSales = $this->getSalesData();
        $formatCustomerBuy = $this->getCustomerData();
        $receipt = $this->getReceiptCount();
        $invoice = $this->getInvoiceCount();
        $paymentData = $this->getPaymentData();

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
                'mo' => $manufacturing,
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
                    'total' => Customer::count(),
                ],
                'customers' => $formatCustomerBuy,
                'sales' => $formatSales,
                'payments' => $paymentData,
            ]
        ]);
    }

    private function getProductCount()
    {
        return Product::count();
    }

    private function getMaterialCount()
    {
        return Material::count();
    }

    private function getBomCount()
    {
        return Bom::count();
    }

    private function getManufacturingCount()
    {
        $manufacturingOrder = ManufacturingOrder::orderBy('created_at', 'DESC')->get();
        return $manufacturingOrder->map(function ($mo) {
            return [
                'id' => $mo->mo_id,
                'reference' => $mo->reference,
                'qty' => $mo->qty,
                'product_name' => $mo->product->product_name,
                'product_internal_reference' => $mo->product->internal_reference,
                'state' => $mo->state,
                'status' => $mo->status,
            ];
        });
    }

    private function getVendorCount()
    {
        return Vendor::count();
    }

    private function getRfqCount()
    {
        return Rfq::count();
    }

    private function getSalesData()
    {
        $sales = Sales::get();
        $income = RegisterPayment::join('invoices', 'register_payments.invoice_id', '=', 'invoices.invoice_id')
            ->where('invoices.transaction_type', 'INV')
            ->where('invoices.payment_status', 3)
            ->sum('register_payments.amount');
        $totalOrder = $sales->where('state', 3)->count('sales_id');
        $totalQuotation = $sales->where('state', '<', 3)->count('sales_id');
        $totalSales = $sales->count('sales_id');

        return [
            'total_data' => $totalSales,
            'total_income' => $income,
            'total_order' => $totalOrder,
            'precentage_order' => $totalSales > 0 ? round(($totalOrder / $totalSales) * 100, 1) : 0,
            'total_quotation' => $totalQuotation,
            'precentage_quotation' => $totalSales > 0 ? round(($totalQuotation / $totalSales) * 100, 1) : 0,
        ];
    }

    private function getCustomerData()
    {
        $sales = Sales::get();
        $customersBuy = Customer::select(
            'customers.customer_id',
            'customers.type',
            'customers.name',
            'customers.company',
            'customers.image_url',
            DB::raw('SUM(sales_components.qty) as total_products'),
            DB::raw('COUNT(DISTINCT sales.sales_id) as purchase_frequency')
        )
            ->join('sales', 'customers.customer_id', '=', 'sales.customer_id')
            ->join('sales_components', 'sales.sales_id', '=', 'sales_components.sales_id')
            ->groupBy('customers.customer_id', 'customers.name', 'customers.company', 'customers.type', 'customers.image_url')
            ->orderByDesc('total_products')
            ->get();

        return $customersBuy->map(function ($customer) use ($sales) {
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
    }

    private function getReceiptCount()
    {
        return Receipt::count();
    }

    private function getInvoiceCount()
    {
        return Invoice::count();
    }

    private function getPaymentData()
    {
        $currentMonthPayments = RegisterPayment::whereYear('payment_date', date('Y'))
            ->whereMonth('payment_date', date('m'))
            ->orderBy('payment_date', 'DESC')
            ->get(['payment_date', 'payment_type', 'amount', 'journal']);

        return $currentMonthPayments->map(function ($payment) {
            return [
                'payment_date' => $payment->payment_date,
                'payment_type' => $payment->payment_type,
                'journal' => $payment->journal,
                'amount' => $payment->amount,
            ];
        });
    }
}
