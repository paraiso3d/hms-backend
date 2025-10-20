<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Exception;

class PatientController extends Controller
{
    /**
     * ✅ Create a new patient
     */
    public function createPatient(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name'        => 'required|string|max:255',
                'age'              => 'required|integer|min:0',
                'gender'           => 'required|string|in:Male,Female,Other',
                'email'            => 'nullable|email|max:255',
                'phone_number'     => 'required|string|max:20',
                'address'          => 'nullable|string|max:255',
                'medical_history'  => 'nullable|string',
                'current_symptoms' => 'required|string',
            ]);

            $patient = Patient::create($validated);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Patient registered successfully!',
                'data'      => $patient
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to register patient.',
                'error'     => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Get all patients
     */
    public function getPatients()
    {
        $patients = Patient::orderBy('created_at', 'desc')->get();

        return response()->json([
            'isSuccess' => true,
            'data' => $patients
        ]);
    }

    /**
     * ✅ Get a single patient by ID
     */
    public function getPatientById($id)
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Patient not found.'
            ], 404);
        }

        return response()->json([
            'isSuccess' => true,
            'data' => $patient
        ]);
    }

    /**
     * ✅ Update patient info
     */
    public function updatePatient(Request $request, $id)
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Patient not found.'
            ], 404);
        }

        $validated = $request->validate([
            'full_name'        => 'required|string|max:255',
            'age'              => 'required|integer|min:0',
            'gender'           => 'required|string|in:Male,Female,Other',
            'email'            => 'nullable|email|max:255',
            'phone_number'     => 'required|string|max:20',
            'address'          => 'nullable|string|max:255',
            'medical_history'  => 'nullable|string',
            'current_symptoms' => 'required|string',
        ]);

        $patient->update($validated);

        return response()->json([
            'isSuccess' => true,
            'message'   => 'Patient updated successfully!',
            'data'      => $patient
        ]);
    }

    /**
     * ✅ Delete patient
     */
    public function deletePatient($id)
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Patient not found.'
            ], 404);
        }

        $patient->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Patient deleted successfully.'
        ]);
    }
}
