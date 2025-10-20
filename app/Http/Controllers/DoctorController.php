<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Appointment;
use Exception;

class DoctorController extends Controller
{
    /**
     * ✅ Register a new doctor (with available days + login credentials)
     */
    public function createDoctor(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctor_name'         => 'required|string|max:255',
                'email'               => 'required|email|unique:doctors,email',
                'password'            => 'required|string|min:6',
                'specialization_id'   => 'required|integer|exists:specializations,id',
                'years_of_experience' => 'required|integer|min:0',
                'consultation_fee'    => 'required|numeric|min:0',
                'qualifications'      => 'required|string|max:255',
                'available_days'      => 'required|array|min:1',
                'available_days.*'    => 'string|max:50',
            ]);

            $doctor = Doctor::create([
                'doctor_name'         => $validated['doctor_name'],
                'email'               => $validated['email'],
                'password'            => Hash::make($validated['password']),
                'specialization_id'   => $validated['specialization_id'],
                'years_of_experience' => $validated['years_of_experience'],
                'consultation_fee'    => $validated['consultation_fee'],
                'qualifications'      => $validated['qualifications'],
                'role'                => 'Doctor',
                'is_archived'         => 0,
            ]);

            foreach ($validated['available_days'] as $day) {
                $doctor->availableDays()->create(['day_of_week' => $day]);
            }

            $doctor->load(['specialization', 'availableDays']);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Doctor account created successfully!',
                'data'      => $doctor,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to create doctor.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Retrieve all active doctors (not archived)
     */
    public function getDoctors()
    {
        try {
            $doctors = Doctor::with(['specialization', 'availableDays'])
                ->where('is_archived', 0)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'isSuccess' => true,
                'message'   => $doctors->isEmpty() ? 'No doctors found.' : 'Doctors retrieved successfully.',
                'data'      => $doctors,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to retrieve doctors.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Retrieve a single doctor by ID
     */
    public function getDoctorById($id)
    {
        try {
            $doctor = Doctor::with(['specialization', 'availableDays'])
                ->where('is_archived', 0)
                ->find($id);

            if (!$doctor) {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'Doctor not found.',
                ], 404);
            }

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Doctor retrieved successfully.',
                'data'      => $doctor,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to retrieve doctor.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Update doctor details and available days
     */
    public function updateDoctor(Request $request, $id)
    {
        try {
            $doctor = Doctor::where('is_archived', 0)->find($id);

            if (!$doctor) {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'Doctor not found or archived.',
                ], 404);
            }

            $validated = $request->validate([
                'doctor_name'         => 'required|string|max:255',
                'email'               => 'required|email|unique:doctors,email,' . $id,
                'password'            => 'nullable|string|min:6',
                'specialization_id'   => 'required|integer|exists:specializations,id',
                'years_of_experience' => 'required|integer|min:0',
                'consultation_fee'    => 'required|numeric|min:0',
                'qualifications'      => 'required|string|max:255',
                'available_days'      => 'required|array|min:1',
                'available_days.*'    => 'string|max:50',
            ]);

            $updateData = [
                'doctor_name'         => $validated['doctor_name'],
                'email'               => $validated['email'],
                'specialization_id'   => $validated['specialization_id'],
                'years_of_experience' => $validated['years_of_experience'],
                'consultation_fee'    => $validated['consultation_fee'],
                'qualifications'      => $validated['qualifications'],
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $doctor->update($updateData);

            $doctor->availableDays()->delete();
            foreach ($validated['available_days'] as $day) {
                $doctor->availableDays()->create(['day_of_week' => $day]);
            }

            $doctor->load(['specialization', 'availableDays']);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Doctor updated successfully.',
                'data'      => $doctor,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to update doctor.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Get appointments for the logged-in doctor
     */
    public function getMyAppointments(Request $request)
    {
        try {
            $doctor = auth()->user();

            if (!$doctor || $doctor->role !== 'Doctor') {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'Unauthorized access.',
                ], 403);
            }

            $appointments = Appointment::where('doctor_id', $doctor->id)
                ->with(['patient'])
                ->orderBy('appointment_date', 'desc')
                ->get();

            return response()->json([
                'isSuccess' => true,
                'message'   => $appointments->isEmpty()
                    ? 'No appointments found for this doctor.'
                    : 'Appointments retrieved successfully.',
                'data'      => $appointments,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to fetch appointments.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }

    public function approveAppointment($id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Appointment not found.',
            ], 404);
        }

        $appointment->status = 'Approved';
        $appointment->save();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Appointment approved successfully.',
            'data' => $appointment,
        ]);
    }

    public function rejectAppointment(Request $request, $id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Appointment not found.',
            ], 404);
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $appointment->status = 'Rejected';
        $appointment->notes = $validated['rejection_reason'] ?? 'No reason provided';
        $appointment->save();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Appointment rejected by doctor.',
            'data' => $appointment,
        ]);
    }

    /**
     * ✅ Soft delete doctor (set is_archived = 1)
     */
    public function deleteDoctor($id)
    {
        try {
            $doctor = Doctor::find($id);

            if (!$doctor) {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'Doctor not found.',
                ], 404);
            }

            $doctor->update(['is_archived' => 1]);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Doctor archived successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to archive doctor.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }
}
