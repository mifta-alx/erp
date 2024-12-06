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
        $relations = $type === 'outbound' ? $payment->vendor : $payment->customer;

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
            'payment_date' => Carbon::parse($payment->payment_date)->setTimezone('+07:00')->format('Y-m-d H:i:s'),
            'memo' => $payment->memo,
            'payment_type' => $payment->payment_type,
        ];
    }



    private function buildInvoiceData($payment, $type)
    {
        $personId = $type === 'outbound' ? 'vendor_id' : 'customer_id';
        $personName = $type === 'outbound' ? 'vendor_name' : 'customer_name';
        $person = $type === 'outbound' ? 'vendor' : 'customer';

        return [
            'id' => $payment->invoice->invoice_id,
            'transaction_type' => $payment->invoice->transaction_type,
            'reference' => $payment->invoice->reference,
            $personId => $payment->invoice->{$personId},
            $personName => $payment->invoice->{$person}->name,
            'rfq_id' => $payment->invoice->rfq_id,
            'invoice_date' => $payment->invoice->invoice_date
                ? Carbon::parse($payment->invoice->invoice_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
            'accounting_date' => $payment->invoice->accounting_date
                ? Carbon::parse($payment->invoice->accounting_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
            'due_date' => $payment->invoice->due_date
                ? Carbon::parse($payment->invoice->due_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
            'payment_terms' => $payment->invoice->paymentTerm->map(function ($paymentTerm) {
                return [
                    'id' => $paymentTerm->payment_term_id,
                    'name' => $paymentTerm->name,
                    'value' => $paymentTerm->value,
                ];
            }),
            'source_document' => $payment->invoice->source_document,
            'taxes' => $payment->invoice->rfq->taxes,
            'total' => $payment->invoice->rfq->total,
            'state' => $payment->invoice->state,
            'payment_status' => $payment->invoice->payment_status,
            'items' => $payment->invoice->rfq->rfqComponent->map(function ($component) {
                return [
                    'component_id' => $component->rfq_component_id,
                    'type' => $component->display_type,
                    'id' => $component->material_id,
                    'internal_reference' => $component->material->internal_reference ?? null,
                    'name' => $component->material->material_name ?? null,
                    'description' => $component->description,
                    'unit_price' => $component->unit_price,
                    'tax' => $component->tax,
                    'subtotal' => $component->subtotal,
                    'qty' => $component->qty,
                    'qty_received' => $component->qty_received,
                    'qty_to_invoice' => $component->qty_to_invoice,
                    'qty_invoiced' => $component->qty_invoiced,
                ];
            }),
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
            $payment_date = Carbon::parse($data['payment_date'])->toIso8601String();
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
                'data' => $this->buildInvoiceData($payment, 'outbound'),
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
