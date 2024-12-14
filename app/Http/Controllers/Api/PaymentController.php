<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\RegisterPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = RegisterPayment::orderBy('payment_id', 'ASC')->get();
        $data = $payments->map(function ($payment) {
            return $this->successResponse($payment, 'outbond');
        });
        return response()->json([
            'success' => true,
            'message' => 'Data Payment',
            'data' => $data
        ]);
    }
    public function show($id)
    {
        $payment = RegisterPayment::find($id);
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }
        return response()->json(['success' => true, 'message' => 'Detail Data Payment Out', 'data' => $this->successResponse($payment, 'outbound')]);
    }

    private function successResponse($payment, $type)
    {
        $relations = $type === 'inbound' ? $payment->vendor : $payment->customer;
        $amountDue = $type === 'inbound' ? $payment->invoice->rfq->total :  $payment->invoice->sales->total;

        return [
            'id' => $payment->payment_id,
            'reference' => $payment->reference,
            'invoice_id' => $payment->invoice_id,
            $type === 'outbound' ? 'vendors' : 'customers' => $relations ? $relations->map(function ($relation) use ($type) {
                return [
                    'id' => $type === 'outbound' ? $relation->vendor_id : $relation->customer_id,
                    'name' => $relation->name,
                ];
            }) : [],
            'journal' => $payment->journal,
            'amount' => $payment->amount,
            'payment_date' => Carbon::parse($payment->payment_date)->format('Y-m-d H:i:s'),
            'memo' => $payment->memo,
            'payment_type' => $payment->payment_type,
            'payment_amount' => $payment
                ? $payment->where('invoice_id', $payment->invoice_id)->sum('amount')
                : 0,
            'amount_due' => $amountDue - ($payment
                ? $payment->where('invoice_id', $payment->invoice_id)->sum('amount')
                : 0),
        ];
    }

    private function buildInvoiceData($payment)
    {
        $isInbound = $payment->payment_type === 'inbound';
        $totalAmount = $isInbound
            ? $payment->invoice->sales->total
            : $payment->invoice->rfq->total;
        $paidAmount = $payment
            ? $payment->where('invoice_id', $payment->invoice_id)->sum('amount')
            : 0;
        return [
            'state' => $payment->invoice->state,
            'payment_status' => $payment->invoice->payment_status,
            'payment_date' => Carbon::parse($payment->payment_date)->format('Y-m-d H:i:s'),
            'payment_amount' => $paidAmount,
            'amount_due' => $totalAmount - $paidAmount,
        ];
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->json()->all();
            $validator = Validator::make(
                $request->all(),
                [
                    'payment_type' => 'required|string',
                    'journal' => 'required|integer',
                    'payment_date' => 'required|date',
                    'amount' => 'required|numeric',
                    'invoice_id' => 'required|exists:invoices,invoice_id',
                    'memo' => 'required|string',
                ]
            );
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $lastOrder = RegisterPayment::where('payment_type', $data['payment_type'])
                ->whereYear('created_at', Carbon::now()->year)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastOrder && $lastOrder->reference) {
                $parts = explode('/', $lastOrder->reference);
                $lastReferenceNumber = isset($parts[2]) ? (int) $parts[2] : 0;
            } else {
                $lastReferenceNumber = 0;
            }

            $ref = $data['journal'] == 1 ? "PBNK" : "PCHS";

            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 5, '0', STR_PAD_LEFT);
            $currentYear = Carbon::now()->year;

            $reference = "{$ref}/{$currentYear}/{$referenceNumberPadded}";
            $payment_date = Carbon::parse($data['payment_date']);
            $payment = RegisterPayment::create([
                'reference' => $reference,
                'invoice_id' => $data['invoice_id'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'journal' => $data['journal'],
                'amount' => $data['amount'],
                'payment_date' => $payment_date,
                'memo' => $data['memo'],
                'payment_type' => $data['payment_type'],
            ]);

            $invoice = Invoice::find($data['invoice_id']);
            if ($invoice) {
                $invoice->update([
                    'payment_status' => $data['payment_status'],
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Payment Successfully Created',
                'data' => $this->buildInvoiceData($payment)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id) {}

    public function destroy($id) {}
}
