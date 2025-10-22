<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Exception;

class PaymentController extends Controller
{
    /**
     * 💰 Create a new payment record
     */
    public function createPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id'       => 'required|exists:patients,id',
                'appointment_id'   => 'nullable|exists:appointments,id',
                'amount'           => 'required|numeric|min:0',
                'payment_method'   => 'required|string|in:Cash,Card,Online,Insurance',
                'payment_status'   => 'required|string|in:Pending,Paid,Failed,Refunded',
                'transaction_date' => 'required|date',
                'remarks'          => 'nullable|string',
            ]);

            // Ensure is_archived defaults to 0
            $validated['is_archived'] = 0;

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
     * 📋 Get all active payments (excluding archived)
     */
    public function getPayments(Request $request)
    {
        try {
            $search  = $request->input('search');
            $perPage = $request->input('per_page', 10); // Default 10 per page

            $query = Payment::with(['patient', 'appointment'])
                ->where('is_archived', 0)
                ->orderBy('transaction_date', 'desc');

            // 🔍 Search logic: patient name, appointment date, or payment fields
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('patient', function ($sub) use ($search) {
                        $sub->where('patient_name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('appointment', function ($sub) use ($search) {
                            $sub->where('appointment_date', 'like', "%{$search}%");
                        })
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            // 📄 Apply pagination
            $payments = $query->paginate($perPage);

            return response()->json([
                'isSuccess' => true,
                'message'   => $payments->isEmpty()
                    ? 'No payments found.'
                    : 'Payments retrieved successfully.',
                'data'      => $payments,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to retrieve payments.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * 🔍 Get payment by ID (only if not archived)
     */
    public function getPaymentById($id)
    {
        $payment = Payment::with(['patient', 'appointment'])
            ->where('id', $id)
            ->where('is_archived', 0)
            ->first();

        if (!$payment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Payment record not found or archived.'
            ], 404);
        }

        return response()->json([
            'isSuccess' => true,
            'data' => $payment
        ]);
    }

    /**
     * ✏️ Update payment record (only if active)
     */
    public function updatePayment(Request $request, $id)
    {
        $payment = Payment::where('id', $id)
            ->where('is_archived', 0)
            ->first();

        if (!$payment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Payment record not found or archived.'
            ], 404);
        }

        $validated = $request->validate([
            'amount'           => 'required|numeric|min:0',
            'payment_method'   => 'required|string|in:Cash,Card,Online,Insurance',
            'payment_status'   => 'required|string|in:Pending,Paid,Failed,Refunded',
            'transaction_date' => 'required|date',
            'remarks'          => 'nullable|string',
        ]);

        $payment->update($validated);

        return response()->json([
            'isSuccess' => true,
            'message'   => 'Payment updated successfully!',
            'data'      => $payment
        ]);
    }

    /**
     * 🗑️ Soft delete (archive) payment record
     */
    public function deletePayment($id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Payment record not found.',
                ], 404);
            }

            $payment->is_archived = 1;
            $payment->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Payment archived successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to archive payment record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
