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
        $sales = Sales::count();
        $customer = Customer::count();
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
                'sale' => [
                    'total' => $sales,
                ],
                'payments' => $paymentData,
            ]
        ]);
    }
}
