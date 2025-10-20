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
            $validated = $request->validate([
                'patient_id'        => 'required|exists:patients,id',
                'doctor_id'         => 'required|exists:doctors,id',
                'appointment_date'  => 'required|date',
                'appointment_time'  => 'required',
                'reason_for_visit'  => 'required|string|max:255',
                'notes'             => 'nullable|string',
            ]);

            $validated['status'] = 'Pending'; //

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
     * ✅ Get all appointments
     */
    public function getAppointments()
    {
        $appointments = Appointment::with(['patient', 'doctor'])
            ->orderBy('appointment_date', 'desc')
            ->get();

        return response()->json([
            'isSuccess' => true,
            'data' => $appointments,
        ]);
    }

    /**
     * ✅ Get appointment by ID
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
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Appointment not found.',
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
     * 🗑️ Delete appointment
     */
    public function deleteAppointment($id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Appointment not found.',
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Appointment deleted successfully.',
        ]);
    }
}
