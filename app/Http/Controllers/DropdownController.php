<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\Appointment;

class DropdownController extends Controller
{
    /**
     * ✅ Get all dropdowns in one request
     */
    public function getAllDropdowns()
    {
        try {
            $patients = Patient::select('id', 'full_name')->get();
            $doctors = Doctor::select('id', 'doctor_name')->get();
            $specializations = Specialization::select('id', 'specialization_name')->get();
            $appointments = Appointment::select('id', 'appointment_date', 'appointment_time')->get();

            return response()->json([
                'isSuccess' => true,
                'patients' => $patients,
                'doctors' => $doctors,
                'specializations' => $specializations,
                'appointments' => $appointments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to load dropdown data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  Patients dropdown
     */
    public function getPatients()
    {
        $patients = Patient::select('id', 'full_name')->get();
        return response()->json(['isSuccess' => true, 'patients' => $patients]);
    }

    /**
     *  Doctors dropdown
     */
    public function getDoctors()
    {
        $doctors = Doctor::select('id', 'doctor_name')->get();
        return response()->json(['isSuccess' => true, 'doctors' => $doctors]);
    }

    /**
     *  Specializations dropdown
     */
    public function getSpecializations()
    {
        $specializations = Specialization::select('id', 'specialization_name')->get();
        return response()->json(['isSuccess' => true, 'specializations' => $specializations]);
    }

    /**
     *  Appointments dropdown
     */
    public function getAppointments()
    {
        $appointments = Appointment::select(
            'id',
            'appointment_date',
            'appointment_time',
            'status'
        )->get();

        return response()->json(['isSuccess' => true, 'appointments' => $appointments]);
    }

    /**
     *  Get doctors filtered by specialization
     */
    public function getDoctorsBySpecialization($specializationId)
    {
        $doctors = Doctor::where('specialization_id', $specializationId)
            ->select('id', 'doctor_name')
            ->get();

        return response()->json(['isSuccess' => true, 'doctors' => $doctors]);
    }
}
