<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentTerm;
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
                    return [
                        'id' => $invoice->invoice_id,
                        'transaction_type' => $invoice->transaction_type,
                        'number' => $invoice->number,
                        'vendor_id' => $invoice->vendor_id,
                        'vendor_name' => $invoice->vendor->name,
                        'rfq_id' => $invoice->rfq_id,
                        'reference' => $invoice->reference,
                        'invoice_date' => $invoice->invoice_date
                            ? Carbon::parse($invoice->invoice_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                        'accounting_date' => $invoice->accounting_date
                            ? Carbon::parse($invoice->accounting_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                        'due_date' => $invoice->due_date
                            ? Carbon::parse($invoice->due_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                        'payment_terms' => $invoice->paymentTerm->map(function ($paymentTerm) {
                            return [
                                'id' => $paymentTerm->payment_term_id,
                                'name' => $paymentTerm->name,
                                'value' => $paymentTerm->value,
                            ];
                        }),
                        'source_document' => $invoice->source_document,
                        'taxes' => $invoice->rfq->taxes,
                        'total' => $invoice->rfq->total,
                        'state' => $invoice->state,
                        'payment_status' => $invoice->payment_status,
                        'items' => $invoice->rfq->rfqComponent->map(function ($component) {
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
                if ($invoice->transaction_type == 'INV') {
                    return [
                        'id' => $invoice->invoice_id,
                        'transaction_type' => $invoice->transaction_type,
                        'reference' => $invoice->reference,
                        'customer_id' => $invoice->customer_id,
                        'customer_name' => $invoice->customer->name,
                        'sales_id' => $invoice->sales_id,
                        'bill_reference' => $invoice->bill_reference,
                        'invoice_date' => $invoice->invoice_date
                            ? Carbon::parse($invoice->invoice_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                        'accounting_date' => $invoice->accounting_date
                            ? Carbon::parse($invoice->accounting_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                        'due_date' => $invoice->due_date
                            ? Carbon::parse($invoice->due_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                        'payment_term_id' => $invoice->paymentTerm->payment_term_id ?? null,
                        'source_document' => $invoice->source_document,
                        'taxes' => $invoice->sales->taxes,
                        'total' => $invoice->sales->total,
                        'state' => $invoice->state,
                        'payment_status' => $invoice->payment_status,
                        'items' => $invoice->sales->salesComponent->map(function ($component) {
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
            return $this->responseBill($invoice, 'Detail Invoice Data');
        } else {
            return $this->responseInv($invoice, 'Detail Invoice Data');
        }
    }

    private function responseBill($invoice, $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $invoice->invoice_id,
                'transaction_type' => $invoice->transaction_type,
                'reference' => $invoice->reference,
                'vendor_id' => $invoice->vendor_id,
                'vendor_name' => $invoice->vendor->name,
                'rfq_id' => $invoice->rfq_id,
                'reference' => $invoice->reference,
                'invoice_date' => $invoice->invoice_date
                    ? Carbon::parse($invoice->invoice_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                'accounting_date' => $invoice->accounting_date
                    ? Carbon::parse($invoice->accounting_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                'due_date' => $invoice->due_date
                    ? Carbon::parse($invoice->due_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                'payment_term_id' => $invoice->paymentTerm->payment_term_id ?? null,
                'source_document' => $invoice->source_document,
                'taxes' => $invoice->rfq->taxes,
                'total' => $invoice->rfq->total,
                'state' => $invoice->state,
                'payment_status' => $invoice->payment_status,
                'payment_date' => $invoice->regPay->payment_date ?? null,
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
            ],
        ], 201);
    }
    private function responseInv($invoice, $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $invoice->invoice_id,
                'transaction_type' => $invoice->transaction_type,
                'number' => $invoice->number,
                'customer_id' => $invoice->customer_id,
                'customer_name' => $invoice->customer->name,
                'sales_id' => $invoice->sales_id,
                'reference' => $invoice->reference,
                'invoice_date' => $invoice->invoice_date
                    ? Carbon::parse($invoice->invoice_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                'accounting_date' => $invoice->accounting_date
                    ? Carbon::parse($invoice->accounting_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                'due_date' => $invoice->due_date
                    ? Carbon::parse($invoice->due_date)->setTimezone('+07:00')->format('Y-m-d H:i:s') : null,
                'payment_term_id' => $invoice->paymentTerm->payment_term_id ?? null,
                'source_document' => $invoice->source_document,
                'taxes' => $invoice->sales->taxes,
                'total' => $invoice->sales->total,
                'state' => $invoice->state,
                'payment_status' => $invoice->payment_status,
                'items' =>  $invoice->sales->salesComponent->map(function ($component) {
                    return [
                        'component_id' => $component->rfq_component_id,
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
            ],
        ], 201);
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

            $invoice = Invoice::create([
                'transaction_type' => $data['transaction_type'],
                'reference' => $reference,
                'rfq_id' => $rfq->rfq_id ?? null,
                'sales_id' => $sales->sales_id ?? null,
                'vendor_id' => $rfq->vendor_id ?? null,
                'customer_id' => $sales->customer_id ?? null,
                'state' => 1,
                'invoice_date' => null,
                'accounting_date' => Carbon::now()->toIso8601String(),
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'source_document' => $rfqReference ?? $salesReference,
                'payment_status' => 1,
            ]);
            DB::commit();
            if ($data['transaction_type'] == 'BILL') {
                return $this->responseBill($invoice, 'Receipt Successfully Added');
            } else {
                return $this->responseInv($invoice, 'Receipt Successfully Added');
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
            $invoice_date = Carbon::parse($data['invoice_date'])->toIso8601String() ?? null;
            $accounting_date = Carbon::parse($data['accounting_date'])->toIso8601String();
            $due_date = Carbon::parse($data['due_date'])->toIso8601String() ?? null;
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
                    $this->processInvTransaction($data, $invoice, $invoice_date, $accounting_date, $due_date);
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
            ? $this->responseBill($invoice, $message)
            : $this->responseInv($invoice, $message);
    }
    private function getValidationRules(Request $request)
    {
        return [
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'invoice_date' => 'required|date',
            'accounting_date' => 'required|date',
            'due_date' => 'required_without:payment_term_id',
            'payment_term_id' => 'nullable|exists:payment_terms,payment_term_id',
            'items.*.qty_invoiced' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $index = str_replace(['items.', '.qty_invoiced'], '', $attribute);
                    $state = $request->input('state');
                    if ($state == 1) {
                        return;
                    } else {
                        $type = $request->input("items.$index.type");
                        if ($type === 'line_section') {
                            return;
                        }
                        $componentId = $request->input("items.$index.component_id");
                        $rfqComponent = RfqComponent::where('rfq_component_id', $componentId)->first();
                        if ($rfqComponent && $value > $rfqComponent->qty_to_invoice) {
                            $fail('Qty invoiced must not exceed the available qty.');
                        }
                    }
                }
            ],
        ];
    }

    private function getValidationMessages()
    {
        return [
            'vendor_id.required' => 'Vendor ID must be filled',
            'vendor_id.exists' => 'Vendor ID does not exist',
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
                return Carbon::parse($data['invoice_date'])->endOfMonth()->toIso8601String();
            } elseif ($paymentTerm) {
                return Carbon::parse($data['invoice_date'])->addDays($paymentTerm->value)->toIso8601String();
            }
        }
        return Carbon::parse($data['due_date'])->toIso8601String();
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
        }
        $invoice->update([
            'sales_id' => $sales->sales_id,
            'customer_id' => $data['customer_id'] ?? null,
            'state' => $data['state'],
            'invoice_date' => $invoice_date,
            'accounting_date' => $accounting_date,
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
                    'qty_to_invoice' => $component['qty_invoiced'],
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
