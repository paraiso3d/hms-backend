<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Exception;

class AppointmentController extends Controller
{
    /**
     * ✅ Create a new appointment (default status = Pending)
     */
    public function createAppointment(Request $request)
    {
        try {
            // ✅ Validate basic input
            $validated = $request->validate([
                'patient_id'        => 'required|exists:patients,id',
                'doctor_id'         => 'required|exists:doctors,id',
                'appointment_date'  => 'required|date|after_or_equal:today',
                'appointment_time'  => 'required|date_format:H:i',
                'reason_for_visit'  => 'required|string|max:255',
                'notes'             => 'nullable|string|max:500',
            ]);

            // 🧩 Check for duplicate appointment (same doctor, date, and time)
            $duplicate = Appointment::where('doctor_id', $validated['doctor_id'])
                ->where('appointment_date', $validated['appointment_date'])
                ->where('appointment_time', $validated['appointment_time'])
                ->where('is_archived', 0)
                ->first();

            if ($duplicate) {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'This time slot is already booked. Please choose a different schedule.',
                ], 409);
            }

            // 🔍 Check if patient already has an active/pending appointment on that date
            $existingPatient = Appointment::where('patient_id', $validated['patient_id'])
                ->where('appointment_date', $validated['appointment_date'])
                ->whereIn('status', ['Pending', 'Approved'])
                ->where('is_archived', 0)
                ->first();

            if ($existingPatient) {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'You already have an appointment scheduled for this date.',
                ], 409);
            }

            // 🩺 Auto-generate Appointment Number
            $latestAppointment = Appointment::latest('id')->first();
            $nextNumber = $latestAppointment ? $latestAppointment->id + 1 : 1;
            $appointmentNo = 'PATIENT' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

            // 🧾 Create appointment record
            $validated['appointment_no'] = $appointmentNo;
            $validated['status'] = 'Pending';
            $validated['is_archived'] = 0;

            $appointment = Appointment::create($validated);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Appointment request submitted successfully. Awaiting doctor approval.',
                'data'      => $appointment,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to create appointment.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ✅ Get all active (non-archived) appointments
     */
    public function getAppointments(Request $request)
    {
        try {
            $search  = $request->input('search');
            $perPage = $request->input('per_page', 10);

            $query = Appointment::with(['patient', 'doctor'])
                ->where('is_archived', 0)
                ->orderBy('appointment_date', 'desc');

            // 🔍 Search logic
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('patient', function ($sub) use ($search) {
                        $sub->where('patient_name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('doctor', function ($sub) use ($search) {
                            $sub->where('doctor_name', 'like', "%{$search}%");
                        })
                        ->orWhere('appointment_date', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $appointments = $query->paginate($perPage);

            // 🖼️ Transform data to include asset URLs
            $appointments->getCollection()->transform(function ($appointment) {
                // Add full URL for patient profile image
                if ($appointment->patient) {
                    $appointment->patient->profile_img = $appointment->patient->profile_img
                        ? asset($appointment->patient->profile_img)
                        : asset('default-profile.png');
                }

                // (Optional) Add doctor profile_img if you want it too
                if ($appointment->doctor) {
                    $appointment->doctor->profile_img = $appointment->doctor->profile_img
                        ? asset($appointment->doctor->profile_img)
                        : asset('default-profile.png');
                }

                return $appointment;
            });

            return response()->json([
                'isSuccess' => true,
                'message' => $appointments->isEmpty()
                    ? 'No appointments found.'
                    : 'Appointments retrieved successfully.',
                'data' => $appointments->items(),
                'pagination' => [
                    'current_page' => $appointments->currentPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'last_page' => $appointments->lastPage(),
                    'has_more_pages' => $appointments->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Error retrieving appointments.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * ✅ Get appointment by ID (including archived)
     */
    public function getAppointmentById($id)
    {
        $appointment = Appointment::with(['patient', 'doctor', 'payment', 'medicalRecord'])
            ->find($id);

        if (!$appointment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Appointment not found.',
            ], 404);
        }

        return response()->json([
            'isSuccess' => true,
            'data' => $appointment,
        ]);
    }

    /**
     * ✅ Update appointment details (for patient updates)
     */
    public function updateAppointment(Request $request, $id)
    {
        $appointment = Appointment::where('is_archived', 0)->find($id);

        if (!$appointment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Appointment not found or has been archived.',
            ], 404);
        }

        $validated = $request->validate([
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'reason_for_visit' => 'required|string|max:255',
            'notes'            => 'nullable|string',
        ]);

        $appointment->update($validated);

        return response()->json([
            'isSuccess' => true,
            'message'   => 'Appointment details updated successfully.',
            'data'      => $appointment,
        ]);
    }

    /**
     * 🚮 Soft delete appointment (archive)
     */
    public function deleteAppointment($id)
    {
        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Appointment not found.',
                ], 404);
            }

            // Mark as archived
            $appointment->is_archived = 1;
            $appointment->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Appointment archived successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to archive appointment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔄 Restore archived appointment (optional helper)
     */
    public function restoreAppointment($id)
    {
        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Appointment not found.',
                ], 404);
            }

            if ($appointment->is_archived == 0) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Appointment is already active.',
                ]);
            }

            $appointment->is_archived = 0;
            $appointment->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Appointment restored successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to restore appointment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
