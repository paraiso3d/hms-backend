<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\Appointment;
use Exception;

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
    public function getSpecializations(Request $request)
    {
        try {
            $search  = $request->input('search');
            $perPage = $request->input('per_page', 10); // Default items per page

            $query = Specialization::select('id', 'specialization_name')
                ->where('is_archived', 0)
                ->orderBy('specialization_name', 'asc');

            // 🔍 Optional search filter
            if (!empty($search)) {
                $query->where('specialization_name', 'like', "%{$search}%");
            }

            // ⚡ Cursor pagination for smooth dropdowns
            $specializations = $query->cursorPaginate($perPage);

            return response()->json([
                'isSuccess' => true,
                'message' => $specializations->isEmpty()
                    ? 'No specializations found.'
                    : 'Specializations retrieved successfully.',
                'specializations' => $specializations->items(), // Actual data only

                // 📄 Minimal pagination data (no URLs)
                'pagination' => [
                    'per_page'       => $specializations->perPage(),
                    'next_cursor'    => optional($specializations->nextCursor())->encode(),
                    'prev_cursor'    => optional($specializations->previousCursor())->encode(),
                    'has_more_pages' => $specializations->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to retrieve specializations.',
                'error'     => $e->getMessage(),
            ], 500);
        }
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
