<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Exception;

class DoctorController extends Controller
{
    /**
     * ✅ Create a new doctor with available days
     */
    public function createDoctor(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctor_name' => 'required|string|max:255',
                'specialization_id' => 'required|integer|exists:specializations,id',
                'years_of_experience' => 'required|integer|min:0',
                'consultation_fee' => 'required|numeric|min:0',
                'qualifications' => 'required|string|max:255',
                'available_days' => 'required|array|min:1',
                'available_days.*' => 'string|max:50',
            ]);

            $doctor = Doctor::create([
                'doctor_name' => $validated['doctor_name'],
                'specialization_id' => $validated['specialization_id'],
                'years_of_experience' => $validated['years_of_experience'],
                'consultation_fee' => $validated['consultation_fee'],
                'qualifications' => $validated['qualifications'],
            ]);

            // 🔗 Insert available days into pivot table
            foreach ($validated['available_days'] as $day) {
                $doctor->availableDays()->create(['day_of_week' => $day]);
            }

            $doctor->load(['specialization', 'availableDays']);

            return response()->json([
                'isSuccess' => true,
                'message' => 'Doctor created successfully!',
                'data' => $doctor,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to create doctor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ✅ Retrieve all doctors
     */
    public function getDoctors()
    {
        try {
            $doctors = Doctor::with(['specialization', 'availableDays'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'isSuccess' => true,
                'message' => $doctors->isEmpty() ? 'No doctors found.' : 'Doctors retrieved successfully.',
                'data' => $doctors,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve doctors.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ✅ Retrieve a single doctor by ID
     */
    public function getDoctorById($id)
    {
        try {
            $doctor = Doctor::with(['specialization', 'availableDays'])->find($id);

            if (!$doctor) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Doctor not found.',
                ], 404);
            }

            return response()->json([
                'isSuccess' => true,
                'message' => 'Doctor retrieved successfully.',
                'data' => $doctor,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve doctor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ✅ Update doctor details and available days
     */
    public function updateDoctor(Request $request, $id)
    {
        try {
            $doctor = Doctor::find($id);

            if (!$doctor) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Doctor not found.',
                ], 404);
            }

            $validated = $request->validate([
                'doctor_name' => 'required|string|max:255',
                'specialization_id' => 'required|integer|exists:specializations,id',
                'years_of_experience' => 'required|integer|min:0',
                'consultation_fee' => 'required|numeric|min:0',
                'qualifications' => 'required|string|max:255',
                'available_days' => 'required|array|min:1',
                'available_days.*' => 'string|max:50',
            ]);

            $doctor->update([
                'doctor_name' => $validated['doctor_name'],
                'specialization_id' => $validated['specialization_id'],
                'years_of_experience' => $validated['years_of_experience'],
                'consultation_fee' => $validated['consultation_fee'],
                'qualifications' => $validated['qualifications'],
            ]);

            // 🔁 Sync available days
            $doctor->availableDays()->delete();
            foreach ($validated['available_days'] as $day) {
                $doctor->availableDays()->create(['day_of_week' => $day]);
            }

            $doctor->load(['specialization', 'availableDays']);

            return response()->json([
                'isSuccess' => true,
                'message' => 'Doctor updated successfully.',
                'data' => $doctor,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to update doctor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ✅ Delete a doctor and their available days
     */
    public function deleteDoctor($id)
    {
        try {
            $doctor = Doctor::find($id);

            if (!$doctor) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Doctor not found.',
                ], 404);
            }

            $doctor->availableDays()->delete();
            $doctor->delete();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Doctor deleted successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to delete doctor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
