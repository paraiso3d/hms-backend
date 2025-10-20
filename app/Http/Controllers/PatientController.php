<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;

class PatientController extends Controller
{
    // Create a new patient account
    public function createPatient(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name'    => 'required|string|max:255',
                'age'          => 'nullable|integer|min:0',
                'gender'       => 'nullable|string|in:Male,Female,Other',
                'email'        => 'required|email|max:255|unique:patients,email',
                'phone_number' => 'nullable|string|max:20',
                'address'      => 'nullable|string|max:255',
                'password'     => 'required|string|min:6|confirmed',
            ]);

            // Hash password before saving
            $validated['password'] = Hash::make($validated['password']);
            $validated['is_archived'] = 0;

            $patient = Patient::create($validated);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Patient account created successfully!',
                'data'      => $patient
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to create patient account.',
                'error'     => $e->getMessage()
            ], 500);
        }
    }

    // Retrieve all active (non-archived) patients
    public function getPatients()
    {
        $patients = Patient::where('is_archived', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'isSuccess' => true,
            'data'      => $patients
        ]);
    }

    // Retrieve a single patient by ID
    public function getPatientById($id)
    {
        $patient = Patient::where('is_archived', 0)->find($id);

        if (!$patient) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Patient not found.'
            ], 404);
        }

        return response()->json([
            'isSuccess' => true,
            'data'      => $patient
        ]);
    }

    // Update a patient profile
    public function updatePatient(Request $request, $id)
    {
        $patient = Patient::where('is_archived', 0)->find($id);

        if (!$patient) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Patient not found.'
            ], 404);
        }

        $validated = $request->validate([
            'full_name'    => 'required|string|max:255',
            'age'          => 'nullable|integer|min:0',
            'gender'       => 'nullable|string|in:Male,Female,Other',
            'email'        => 'required|email|max:255|unique:patients,email,' . $id,
            'phone_number' => 'nullable|string|max:20',
            'address'      => 'nullable|string|max:255',
            'password'     => 'nullable|string|min:6|confirmed',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $patient->update($validated);

        return response()->json([
            'isSuccess' => true,
            'message'   => 'Patient profile updated successfully!',
            'data'      => $patient
        ]);
    }

    // Soft delete (archive) a patient account
    public function deletePatient($id)
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Patient not found.'
            ], 404);
        }

        $patient->update(['is_archived' => 1]);

        return response()->json([
            'isSuccess' => true,
            'message'   => 'Patient account archived successfully.'
        ]);
    }
}
