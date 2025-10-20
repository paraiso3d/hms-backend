<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Exception;

class PaymentController extends Controller
{
    /**
     * ✅ Create a new payment record
     */
    public function createPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id'      => 'required|exists:patients,id',
                'appointment_id'  => 'nullable|exists:appointments,id',
                'amount'          => 'required|numeric|min:0',
                'payment_method'  => 'required|string|in:Cash,Card,Online,Insurance',
                'payment_status'  => 'required|string|in:Pending,Paid,Failed,Refunded',
                'transaction_date' => 'required|date',
                'remarks'         => 'nullable|string',
            ]);

            $payment = Payment::create($validated);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Payment recorded successfully!',
                'data'      => $payment
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to create payment record.',
                'error'     => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Get all payments
     */
    public function getPayments()
    {
        $payments = Payment::with(['patient', 'appointment'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json([
            'isSuccess' => true,
            'data' => $payments
        ]);
    }

    /**
     * ✅ Get payment by ID
     */
    public function getPaymentById($id)
    {
        $payment = Payment::with(['patient', 'appointment'])->find($id);

        if (!$payment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Payment record not found.'
            ], 404);
        }

        return response()->json([
            'isSuccess' => true,
            'data' => $payment
        ]);
    }

    /**
     * ✅ Update payment record
     */
    public function updatePayment(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Payment record not found.'
            ], 404);
        }

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:0',
            'payment_method'  => 'required|string|in:Cash,Card,Online,Insurance',
            'payment_status'  => 'required|string|in:Pending,Paid,Failed,Refunded',
            'transaction_date' => 'required|date',
            'remarks'         => 'nullable|string',
        ]);

        $payment->update($validated);

        return response()->json([
            'isSuccess' => true,
            'message'   => 'Payment updated successfully!',
            'data'      => $payment
        ]);
    }

    /**
     * ✅ Delete payment record
     */
    public function deletePayment($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Payment record not found.'
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Payment deleted successfully.'
        ]);
    }
}
