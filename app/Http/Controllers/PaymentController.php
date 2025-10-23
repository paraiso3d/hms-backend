<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Exception;

class PaymentController extends Controller
{


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
     * 💵 Confirm a payment (mark as Paid)
     */
    public function confirmPayment($id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Payment record not found.',
                ], 404);
            }

            if ($payment->is_archived) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Cannot confirm an archived payment.',
                ], 400);
            }

            // 🚫 Prevent re-confirming
            if ($payment->payment_status === 'Paid') {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'This payment has already been confirmed as Paid.',
                ], 400);
            }

            // ✅ Update payment status
            $payment->payment_status = 'Paid';
            $payment->payment_date = now();
            $payment->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Payment confirmed successfully.',
                'data' => $payment,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to confirm payment.',
                'error' => $e->getMessage(),
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
