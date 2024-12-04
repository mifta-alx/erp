<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTerm;
use Illuminate\Http\Request;

class PaymentTermController extends Controller
{
    public function index() {
        $paymentTerm = PaymentTerm::orderBy('payment_term_id', 'ASC')->get();
        return response()->json([
            'success' => true,
            'message' => 'List Payment Term Data',
            'data' => $paymentTerm->map(function($payment){
                return[
                    'id' => $payment->payment_term_id,
                    'name' => $payment->name,
                    'value' => $payment->value,
                ];
            }),
        ], 201);
    }

    public function show($id) {
        $paymentTerm = PaymentTerm::find($id);
        if (!$paymentTerm) {
            return response()->json([
                'success' => false,
                'message' => 'Payment Term not found',
            ], 404);
        }
        return $this->successResponse($paymentTerm, 'Detail Data Payment Term');
    }

    private function successResponse($paymentTerm, $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $paymentTerm->payment_term_id,
                'name' => $paymentTerm->name,
                'value' => $paymentTerm->value,
            ],
        ], 201);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->json()->all();
            $paymentTerm = PaymentTerm::create([
                'name' => $data['name'],
                'value' => $data['value'],
            ]);
            return $this->successResponse($paymentTerm, 'Payment Term Successfully Created');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id) {
        try {
            $data = $request->json()->all();
            $paymentTerm = PaymentTerm::find($id);
            $paymentTerm->update([
                'name' => $data['name'],
                'value' => $data['value'],
            ]);
            return $this->successResponse($paymentTerm, 'Payment Term Successfully Updated');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id) {}
}
