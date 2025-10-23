<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Exception;

class DashboardController extends Controller
{
    // 🩺 ADMIN DASHBOARD
    public function getAdminDashboard()
    {
        try {
            $totalDoctors = Doctor::where('is_archived', 0)->count();
            $totalPatients = Patient::where('is_archived', 0)->count();
            $totalAppointments = Appointment::where('is_archived', 0)->count();

            // 💰 Get total earnings from payments table (Paid only)
            $totalEarnings = Payment::where('is_archived', 0)
                ->where('payment_status', 'Paid')
                ->sum('amount');

            $latestAppointments = Appointment::with(['doctor', 'patient'])
                ->orderBy('appointment_date', 'desc')
                ->take(5)
                ->get()
                ->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'patient_name' => $a->patient->full_name ?? 'Unknown',
                        'doctor_name' => $a->doctor->doctor_name ?? 'Unknown',
                        'appointment_date' => $a->appointment_date,
                        'status' => $a->status,
                    ];
                });

            // Top doctors by completed appointment count
            $topDoctors = Doctor::select('id', 'doctor_name', 'specialization_id')
                ->withCount(['appointments' => function ($q) {
                    $q->where('status', 'Completed');
                }])
                ->orderBy('appointments_count', 'desc')
                ->take(5)
                ->get()
                ->map(function ($doc) {
                    return [
                        'doctor_name' => $doc->doctor_name,
                        'specialization' => $doc->specialization->specialization_name ?? 'N/A',
                        'appointments_completed' => $doc->appointments_count,
                    ];
                });

            return response()->json([
                'isSuccess' => true,
                'message' => 'Admin dashboard data retrieved successfully.',
                'data' => [
                    'summary' => [
                        'doctors' => $totalDoctors,
                        'patients' => $totalPatients,
                        'appointments' => $totalAppointments,
                        'earnings' => $totalEarnings,
                    ],
                    'latestAppointments' => $latestAppointments,
                    'topDoctors' => $topDoctors,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to fetch admin dashboard data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // 💼 DOCTOR DASHBOARD
    public function getDoctorDashboard(Request $request)
    {
        try {
            $doctor = auth()->user();

            if (!$doctor || $doctor->role !== 'Doctor') {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            $totalAppointments = Appointment::where('doctor_id', $doctor->id)
                ->where('is_archived', 0)
                ->count();

            $totalPatients = Appointment::where('doctor_id', $doctor->id)
                ->where('is_archived', 0)
                ->distinct('patient_id')
                ->count('patient_id');

            // 💰 Calculate total earnings for this doctor
            $earnings = Payment::join('appointments', 'payments.appointment_id', '=', 'appointments.id')
                ->where('appointments.doctor_id', $doctor->id)
                ->where('payments.payment_status', 'Paid')
                ->where('payments.is_archived', 0)
                ->sum('payments.amount');

            $latestBookings = Appointment::with('patient')
                ->where('doctor_id', $doctor->id)
                ->where('is_archived', 0)
                ->orderBy('appointment_date', 'desc')
                ->take(5)
                ->get()
                ->map(function ($a) {
                    return [
                        'patient_name' => $a->patient->full_name ?? 'Unknown',
                        'status' => $a->status,
                    ];
                });

            return response()->json([
                'isSuccess' => true,
                'message' => 'Doctor dashboard data retrieved successfully.',
                'data' => [
                    'summary' => [
                        'earnings' => $earnings,
                        'appointments' => $totalAppointments,
                        'patients' => $totalPatients,
                    ],
                    'latestBookings' => $latestBookings,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to fetch doctor dashboard data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
