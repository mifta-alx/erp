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
                        'payment_terms' => $invoice->payment_terms,
                        'source_document' => $invoice->source_document,
                        'taxes' => $invoice->rfq->taxes,
                        'total' => $invoice->rfq->total,
                        'items' => $invoice->rfq->rfqComponent
                            ->filter(function ($component) {
                                return $component->display_type !== 'line_section';
                            })
                            ->values()->map(function ($component) {
                                return [
                                    'component_id' => $component->rfq_component_id,
                                    'type' => $component->display_type,
                                    'id' => $component->material_id,
                                    'internal_reference' => $component->material->internal_reference,
                                    'name' => $component->material->material_name,
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
                        'payment_terms' => $invoice->payment_terms,
                        'source_document' => $invoice->source_document,
                        'taxes' => $invoice->sales->taxes,
                        'total' => $invoice->sales->total,
                        'items' => $invoice->sales->salesComponent
                            ->filter(function ($component) {
                                return $component->display_type !== 'line_section';
                            })
                            ->values()->map(function ($component) {
                                return [
                                    'component_id' => $component->sales_component_id,
                                    'type' => $component->display_type,
                                    'id' => $component->product_id,
                                    'internal_reference' => $component->product->internal_reference,
                                    'name' => $component->product->product_name,
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
                'payment_term_id' => $invoice->payment_term_id,
                'source_document' => $invoice->source_document,
                'taxes' => $invoice->rfq->taxes,
                'total' => $invoice->rfq->total,
                'items' =>  $invoice->rfq->rfqComponent->filter(function ($component) {
                    return $component->display_type !== 'line_section';
                })->map(function ($component) {
                    return [
                        'component_id' => $component->rfq_component_id,
                        'type' => $component->display_type,
                        'id' => $component->material_id,
                        'internal_reference' => $component->material->internal_reference,
                        'name' => $component->material->material_name,
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
                'payment_term_id' => $invoice->payment_term_id,
                'source_document' => $invoice->source_document,
                'taxes' => $invoice->sales->taxes,
                'total' => $invoice->sales->total,
                'items' =>  $invoice->sales->salesComponent->filter(function ($component) {
                    return $component->display_type !== 'line_section';
                })->map(function ($component) {
                    return [
                        'component_id' => $component->sales_component_id,
                        'type' => $component->display_type,
                        'id' => $component->product_id,
                        'internal_reference' => $component->product->internal_reference,
                        'name' => $component->product->product_name,
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
            } else {
                $sales = Sales::findOrFail($data['sales_id']);
                $salesReference = $sales->reference;
            }

            $invoice = Invoice::create([
                'transaction_type' => $data['transaction_type'],
                'reference' => 'Draft',
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
            $invoice = Invoice::find($id);
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
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

            $invoice_date = Carbon::parse($data['invoice_date'])->toIso8601String() ?? null;
            $accountingDate = Carbon::parse($data['accounting_date'])->toIso8601String() ?? null;

            $paymentTerm = PaymentTerm::find($data['payment_term_id']);
            if ($paymentTerm->name == 'End of This Month') {
                $due_date = Carbon::parse($data['invoice_date'])->endOfMonth()->toIso8601String();
            } else {
                $due_date = Carbon::parse($data['invoice_date'])->addDays($paymentTerm->value)->toIso8601String();
            }

            if ($data['transaction_type'] == 'BILL') {
                $invoice->update([
                    'transaction_type' => $data['transaction_type'],
                    'reference' => $reference,
                    'rfq_id' => $rfq->rfq_id ?? null,
                    'vendor_id' => $data['vendor_id'],
                    'state' => $data['state'],
                    'invoice_date' => $invoice_date,
                    'acounting_date' => $accountingDate,
                    'payment_term_id' => $data['payment_term_id'] ?? null,
                    'due_date' => $data['due_date'] ?? $due_date,
                ]);
                if ($data['state'] == 2) {
                    $invoice->update([
                        'transaction_type' => $data['transaction_type'],
                        'reference' => $reference,
                        'rfq_id' => $rfq->rfq_id ?? null,
                        'vendor_id' => $data['vendor_id'] ?? null,
                        'bill_reference' => $data['bill_reference'] ?? null,
                        'state' => $data['state'],
                        'invoice_date' => $invoice_date,
                        'acounting_date' => $accountingDate,
                        'payment_term_id' => $data['payment_term_id'] ?? null,
                        'due_date' => $data['due_date']  ?? $due_date,
                    ]);
                    foreach ($data['items'] as $component) {
                        $rfqComponent = RfqComponent::where('rfq_id', $rfq->rfq_id)->where('rfq_component_id', $component['component_id'])->first();
                        if ($rfqComponent) {
                            $rfqComponent->update([
                                'qty_to_invoice' => $rfqComponent->qty_to_invoice - $component['qty_invoiced'],
                                'qty_invoiced' => $component['qty_invoiced'] + $rfqComponent->qty_invoiced,
                            ]);
                        }
                        if ($rfq) {
                            $rfq->update([
                                'invoice_status' => $data['invoice_status'],
                            ]);
                        }
                    }
                }
            } else if ($data['transaction_type'] == 'INV') {
                $invoice->update([
                    'transaction_type' => $data['transaction_type'],
                    'reference' => $reference,
                    'sales_id' => $sales->sales_id ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                    'bill_reference' => $data['bill_reference'] ?? null,
                    'state' => $data['state'],
                    'invoice_date' => $invoice_date,
                    'payment_term_id' => $data['payment_term_id'] ?? null,
                    'due_date' => $data['due_date'] ?? $due_date,
                ]);
                if ($data['state'] == 2) {
                    $invoice->update([
                        'transaction_type' => $data['transaction_type'],
                        'reference' => $reference,
                        'sales_id' => $sales->sales_id ?? null,
                        'customer_id' => $data['customer_id'] ?? null,
                        'bill_reference' => $data['bill_reference'] ?? null,
                        'state' => $data['state'],
                        'invoice_date' => $invoice_date,
                        'payment_term_id' => $data['payment_term_id'] ?? null,
                        'due_date' => $data['due_date']  ?? $due_date,
                    ]);
                    foreach ($data['items'] as $component) {
                        $salesComponent = SalesComponent::where('sales_id', $sales->sales_id)->where('sales_component_id', $component['component_id'])->first();
                        if ($salesComponent) {
                            $salesComponent->update([
                                'qty_to_invoice' => $salesComponent->qty_to_invoice - $component['qty_invoiced'],
                                'qty_invoiced' => $component['qty_invoiced'] + $salesComponent->qty_invoiced,
                            ]);
                        }
                        if ($sales) {
                            $sales->update([
                                'invoice_status' => $data['invoice_status'],
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            if ($data['transaction_type'] == 'BILL') {
                return $this->responseBill($invoice, 'Receipt Successfully Updated');
            } else {
                return $this->responseInv($invoice, 'Receipt Successfully Updated');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id) {}
}
