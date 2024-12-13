<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentTerm;
use App\Models\Receipt;
use App\Models\RegisterPayment;
use App\Models\Rfq;
use App\Models\RfqComponent;
use App\Models\Sales;
use App\Models\SalesComponent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::orderBy('created_at', 'DESC')->get();
        return response()->json([
            'success' => true,
            'message' => 'List Invoice Data',
            'data' => $invoices->map(function ($invoice) {
                if ($invoice->transaction_type == 'BILL') {
                    return $this->responseBill($invoice);
                }
                if ($invoice->transaction_type == 'INV') {
                    return $this->responseInv($invoice);
                }
            })
        ], 201);
    }
    public function show($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->transaction_type == 'BILL') {
            return response()->json([
                'success' => true,
                'message' => 'Detail Invoice Data',
                'data' => $this->responseBill($invoice)
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Detail Invoice Data',
                'data' => $this->responseInv($invoice)
            ]);
        }
    }

    private function responseBill($invoice)
    {
        return [
            'id' => $invoice->invoice_id,
            'transaction_type' => $invoice->transaction_type,
            'reference' => $invoice->reference,
            'vendor_id' => $invoice->vendor_id,
            'vendor_name' => $invoice->vendor->name,
            'rfq_id' => $invoice->rfq_id,
            'reference' => $invoice->reference,
            'invoice_date' => $invoice->invoice_date
                ? Carbon::parse($invoice->invoice_date)->format('Y-m-d H:i:s') : null,
            'accounting_date' => $invoice->accounting_date
                ? Carbon::parse($invoice->accounting_date)->format('Y-m-d H:i:s') : null,
            'due_date' => $invoice->due_date
                ? Carbon::parse($invoice->due_date)->format('Y-m-d H:i:s') : null,
            'payment_term_id' => $invoice->paymentTerm->payment_term_id ?? null,
            'source_document' => $invoice->source_document,
            'taxes' => $invoice->rfq->taxes,
            'total' => $invoice->rfq->total,
            'state' => $invoice->state,
            'payment_status' => $invoice->payment_status,
            'payment_date' => $invoice->regPay->payment_date ?? null,
            'payment_amount' => $invoice->regPay
                ? $invoice->regPay->where('invoice_id', $invoice->invoice_id)->sum('amount')
                : 0,
            'amount_due' => $invoice->rfq->total - ($invoice->regPay
                ? $invoice->regPay->where('invoice_id', $invoice->invoice_id)->sum('amount')
                : 0),
            'items' =>  $invoice->rfq->rfqComponent->map(function ($component) {
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
    private function responseInv($invoice)
    {
        return [
            'id' => $invoice->invoice_id,
            'transaction_type' => $invoice->transaction_type,
            'reference' => $invoice->reference,
            'customer_id' => $invoice->customer_id,
            'customer_name' => $invoice->customer->name,
            'sales_id' => $invoice->sales_id,
            'reference' => $invoice->reference,
            'invoice_date' => $invoice->invoice_date
                ? Carbon::parse($invoice->invoice_date)->format('Y-m-d H:i:s') : null,
            'accounting_date' => $invoice->accounting_date
                ? Carbon::parse($invoice->accounting_date)->format('Y-m-d H:i:s') : null,
            'due_date' => $invoice->due_date
                ? Carbon::parse($invoice->due_date)->format('Y-m-d H:i:s') : null,
            'delivery_date' => $invoice->delivery_date
                ? Carbon::parse($invoice->delivery_date)->format('Y-m-d H:i:s') : null,
            'payment_term_id' => $invoice->payment_term_id ?? null,
            'source_document' => $invoice->source_document,
            'taxes' => $invoice->sales->taxes,
            'total' => $invoice->sales->total,
            'state' => $invoice->state,
            'payment_status' => $invoice->payment_status,
            'payment_date' => $invoice->regPay->payment_date ?? null,
            'payment_amount' => $invoice->regPay
                ? $invoice->regPay->where('invoice_id', $invoice->invoice_id)->sum('amount')
                : 0,
            'amount_due' => $invoice->sales->total - ($invoice->regPay
                ? $invoice->regPay->where('invoice_id', $invoice->invoice_id)->sum('amount')
                : 0),
            'items' =>  $invoice->sales->salesComponents->map(function ($component) {
                return [
                    'component_id' => $component->sales_component_id,
                    'type' => $component->display_type,
                    'id' => $component->product_id,
                    'internal_reference' => $component->product->internal_reference ?? null,
                    'name' => $component->product->product_name ?? null,
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
            if ($data['transaction_type'] == "BILL") {
                $rfq = Rfq::findOrFail($data['rfq_id']);
                $rfqReference = $rfq->reference;

                $unfinishedInvoice = Invoice::where('rfq_id', $rfq->rfq_id)
                    ->where(function ($query) {
                        $query->where('state', '!=', 2)
                            ->orWhere('payment_status', '=', 1);
                    })
                    ->exists();

                if ($unfinishedInvoice) {
                    return response()->json([
                        'success' => false,
                        'message' => 'There are unfinished or unpaid invoices related to this RFQ. Please complete or pay them first.',
                    ], 400);
                }
            } else {
                $sales = Sales::findOrFail($data['sales_id']);
                $salesReference = $sales->reference;
                $unfinishedInvoice = Invoice::where('sales_id', $sales->sales_id)
                    ->where(function ($query) {
                        $query->where('state', '!=', 2)
                            ->orWhere('payment_status', '=', 1);
                    })
                    ->exists();

                if ($unfinishedInvoice) {
                    return response()->json([
                        'success' => false,
                        'message' => 'There are unfinished or unpaid invoices related to this Sales. Please complete or pay them first.',
                    ], 400);
                }
            }

            if ($data['transaction_type'] == 'BILL') {
                $lastOrder = Invoice::where('transaction_type', 'BILL')
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $rfq = Rfq::findOrFail($data['rfq_id']);
            } else {
                $lastOrder = Invoice::where('transaction_type', 'INV')
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $sales = Sales::findOrFail($data['sales_id']);
                $receipt = Receipt::where('sales_id', $sales->sales_id)->first();
                $deliveryDate = $receipt->updated_at;
            }

            if ($lastOrder && $lastOrder->reference) {
                $lastReferenceNumber = (int) substr($lastOrder->reference, -4);
            } else {
                $lastReferenceNumber = 0;
            }

            $referenceNumber = $lastReferenceNumber + 1;
            $referenceNumberPadded = str_pad($referenceNumber, 4, '0', STR_PAD_LEFT);
            $currentYear = Carbon::now()->year;
            $currentMonth = str_pad(Carbon::now()->month, 2, '0', STR_PAD_LEFT);
            $reference = "{$currentYear}/{$currentMonth}/{$referenceNumberPadded}";
            if ($data['transaction_type'] == 'BILL') {
                $accounting_date = Carbon::now()->setTimezone('+07:00');
            }
            $invoice = Invoice::create([
                'transaction_type' => $data['transaction_type'],
                'reference' => $reference,
                'rfq_id' => $rfq->rfq_id ?? null,
                'sales_id' => $sales->sales_id ?? null,
                'vendor_id' => $rfq->vendor_id ?? null,
                'customer_id' => $sales->customer_id ?? null,
                'state' => 1,
                'invoice_date' => null,
                'accounting_date' => $accounting_date ?? null,
                'delivery_date' => $deliveryDate ?? null,
                'payment_term_id' => $sales->payment_term_id ?? null,
                'due_date' => $data['due_date'] ?? null,
                'source_document' => $rfqReference ?? $salesReference,
                'payment_status' => 1,
            ]);
            DB::commit();
            if ($data['transaction_type'] == 'BILL') {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice Successfully Added',
                    'data' => $this->responseBill($invoice),
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice Successfully Added',
                    'data' => $this->responseInv($invoice),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $data = $request->json()->all();
            $invoice_date = Carbon::parse($data['invoice_date']) ?? null;
            $accounting_date = Carbon::parse($data['accounting_date']);
            $due_date = Carbon::parse($data['due_date']) ?? null;
            if ($data['action_type'] !== 'confirm') {
                $invoice = Invoice::find($id);
                if (!$invoice) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice not found',
                    ], 404);
                }

                if ($data['transaction_type'] === 'BILL') {
                    $this->processBillTransaction($data, $invoice, $invoice_date, $accounting_date, $due_date);
                } else if ($data['transaction_type'] === 'INV') {
                    $deliveryDate = Carbon::parse($data['delivery_date']);
                    $this->processInvTransaction($data, $invoice, $invoice_date, $accounting_date, $due_date, $deliveryDate);
                }

                DB::commit();
                return $this->generateResponse($data['transaction_type'], $invoice, 'Receipt Successfully Updated');
            }

            $validator = Validator::make($data, $this->getValidationRules($request), $this->getValidationMessages($request));
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $invoice = Invoice::find($id);
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                ], 404);
            }
            if ($data['transaction_type'] === 'BILL') {
                $this->processBillTransaction($data, $invoice, $invoice_date, $accounting_date, $due_date);
            } else if ($data['transaction_type'] === 'INV') {
                $this->processInvTransaction($data, $invoice, $invoice_date, $accounting_date, $due_date);
            }

            DB::commit();
            return $this->generateResponse($data['transaction_type'], $invoice, 'Receipt Successfully Updated');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    private function generateResponse($transactionType, $invoice, $message)
    {
        return $transactionType === 'BILL'
            ? response()->json([
                'success' => true,
                'message' => $message,
                'data' => $this->responseBill($invoice)
            ])
            : response()->json([
                'success' => true,
                'message' => $message,
                'data' => $this->responseInv($invoice)
            ]);
    }
    private function getValidationRules(Request $request)
    {
        return [
            'vendor_id' => [
                'required_without:sales_id',
                'exists:vendors,vendor_id',
            ],
            'sales_id' => [
                'required_without:vendor_id',
                'exists:sales,sales_id',
            ],
            'invoice_date' => 'required',
            'accounting_date' => ['required_without:sales_id'],
            'due_date' => 'required_without:payment_term_id',
            'payment_term_id' => 'nullable|exists:payment_terms,payment_term_id',
            'items.*.qty_invoiced' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $transactionType = $request->input('transaction_type');
                    $index = str_replace(['items.', '.qty_invoiced'], '', $attribute);
                    $state = $request->input('state');
                    $componentId = $request->input("items.$index.component_id");
                    $type = $request->input("items.$index.type");

                    if ($state == 1 || $type === 'line_section') {
                        return;
                    }

                    if ($transactionType == 'BILL') {
                        $this->validateRfqComponent($componentId, $value, $fail);
                    }

                    if ($transactionType == 'INV') {
                        $this->validateSalesComponent($componentId, $value, $fail);
                    }
                }
            ],
        ];
    }

    protected function validateRfqComponent($componentId, $value, $fail)
    {
        $rfqComponent = RfqComponent::where('rfq_component_id', $componentId)->first();
        if ($rfqComponent && $value > $rfqComponent->qty_to_invoice) {
            $fail('Qty invoiced must not exceed the available qty for RFQ.');
        }
    }

    protected function validateSalesComponent($componentId, $value, $fail)
    {
        $salesComponent = SalesComponent::where('sales_component_id', $componentId)->first();
        if ($salesComponent && $value > $salesComponent->qty_to_invoice) {
            $fail('Qty invoiced must not exceed the available qty for Sales.');
        }
    }

    private function getValidationMessages()
    {
        return [
            'vendor_id.required_without' => 'Vendor ID must be provided if Sales ID is not selected.',
            'vendor_id.exists' => 'Vendor ID does not exist.',
            'sales_id.required_without' => 'Sales ID must be provided if Vendor ID is not selected.',
            'sales_id.exists' => 'Sales ID does not exist.',
            'invoice_date.required' => 'Invoice date must be filled',
            'accounting_date.required' => 'Accounting date must be filled',
            'due_date.required_without' => 'Due date must be provided if payment term is not selected',
        ];
    }

    private function calculateDueDate(array $data)
    {
        if (isset($data['payment_term_id'])) {
            $paymentTerm = PaymentTerm::find($data['payment_term_id']);
            if ($paymentTerm && $paymentTerm->name === 'End of This Month') {
                return Carbon::parse($data['invoice_date'])->endOfMonth();
            } elseif ($paymentTerm) {
                return Carbon::parse($data['invoice_date'])->addDays($paymentTerm->value);
            }
        }
        return Carbon::parse($data['due_date']);
    }

    private function processBillTransaction(array $data, $invoice, $invoice_date, $accounting_date, $due_date)
    {
        $rfq = Rfq::findOrFail($data['rfq_id']);
        if ($data['state'] == 1) {
            $payment_status = 1;
        } else {
            $payment_status = 1;
        }

        $invoice->update([
            'rfq_id' => $rfq->rfq_id,
            'vendor_id' => $data['vendor_id'],
            'state' => $data['state'],
            'invoice_date' => $invoice_date,
            'accounting_date' => $accounting_date,
            'due_date' => $due_date,
            'payment_term_id' => $data['payment_term_id'] ?? null,
            'payment_status' => $payment_status,
        ]);

        foreach ($data['items'] as $component) {
            $this->updateRfqComponent($rfq->rfq_id, $component, 1);
        }

        if ($data['state'] === 2) {
            $invoice->update([
                'rfq_id' => $rfq->rfq_id,
                'vendor_id' => $data['vendor_id'],
                'state' => $data['state'],
                'invoice_date' => $invoice_date,
                'accounting_date' => $accounting_date,
                'due_date' => $due_date,
                'payment_term_id' => $data['payment_term_id'] ?? null,
            ]);
            foreach ($data['items'] as $component) {
                $this->updateRfqComponent($rfq->rfq_id, $component, 2);
            }
            $rfq->update([
                'taxes' => $data['taxes'],
                'total' => $data['total'],
                'invoice_status' => $data['invoice_status'],
            ]);
        }
    }

    private function processInvTransaction(array $data, $invoice, $invoice_date, $accounting_date, $due_date)
    {
        $sales = Sales::findOrFail($data['sales_id']);
        if ($data['state'] == 1) {
            $payment_status = 1;
        } else {
            $payment_status = 1;
        }
        $invoice->update([
            'sales_id' => $sales->sales_id,
            'customer_id' => $data['customer_id'] ?? null,
            'state' => $data['state'],
            'invoice_date' => $invoice_date,
            'accounting_date' => null,
            'delivery_date' => $data['delivery_date'] ?? $invoice->delivery_date,
            'due_date' => $due_date,
            'payment_term_id' => $data['payment_term_id'] ?? null,
            'payment_status' => $payment_status,
        ]);

        foreach ($data['items'] as $component) {
            $this->updateSalesComponent($sales->sales_id, $component, 1);
        }

        if ($data['state'] === 2) {
            $invoice->update([
                'sales_id' => $sales->sales_id,
                'customer_id' => $data['customer_id'] ?? null,
                'state' => $data['state'],
                'invoice_date' => $invoice_date,
                'accounting_date' => $accounting_date,
                'delivery_date' => $data['delivery_date'] ?? $invoice->delivery_date,
                'due_date' => $due_date,
                'payment_term_id' => $data['payment_term_id'] ?? null,
            ]);

            foreach ($data['items'] as $component) {
                $this->updateSalesComponent($sales->sales_id, $component, 2);
            }
            $sales->update([
                'taxes' => $data['taxes'],
                'total' => $data['total'],
                'invoice_status' => $data['invoice_status'],
            ]);
        }
    }

    private function updateRfqComponent($rfq_id, $component, $mes)
    {
        $rfqComponent = RfqComponent::where('rfq_id', $rfq_id)->where('rfq_component_id', $component['component_id'])->first();
        if ($mes == 2) {
            if ($rfqComponent) {
                $rfqComponent->update([
                    'qty_to_invoice' => max(0, $rfqComponent->qty_to_invoice - $component['qty_invoiced']),
                    'qty_invoiced' => $component['qty_invoiced'] + $rfqComponent->qty_invoiced,
                ]);
            }
        } else {
            if ($rfqComponent) {
                $rfqComponent->update([
                    'qty_to_invoice' => $rfqComponent->qty_to_invoice == 0
                        ? $component['qty_invoiced']
                        : $rfqComponent->qty_to_invoice,
                    'qty_invoiced' => 0,
                ]);
            }
        }
    }

    private function updateSalesComponent($sales_id, $component, $mes)
    {
        $salesComponent = SalesComponent::where('sales_id', $sales_id)->where('sales_component_id', $component['component_id'])->first();
        if ($mes == 2) {
            if ($salesComponent) {
                $salesComponent->update([
                    'qty_to_invoice' => max(0, $salesComponent->qty_to_invoice - $component['qty_invoiced']),
                    'qty_invoiced' => $component['qty_invoiced'] + $salesComponent->qty_invoiced,
                ]);
            }
        } else {
            if ($salesComponent) {
                $salesComponent->update([
                    'qty_to_invoice' => $component['qty_invoiced'],
                    'qty_invoiced' => 0,
                ]);
            }
        }
    }
    public function destroy($id) {}
}
