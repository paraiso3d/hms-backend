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
                'profile_img'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Hash password before saving
            $validated['password'] = Hash::make($validated['password']);
            $validated['is_archived'] = 0;

            // Save uploaded profile image (if provided)
            if ($request->hasFile('profile_img')) {
                $validated['profile_img'] = $this->saveFileToPublic($request, 'profile_img', 'patient');
            }

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
    public function getPatients(Request $request)
    {
        try {
            $search  = $request->input('search');
            $perPage = $request->input('per_page', 10);

            $query = Patient::where('is_archived', 0)
                ->orderBy('created_at', 'desc');

            // 🔍 Search logic
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            }

            $patients = $query->paginate($perPage);

            // 🖼️ Transform to include asset URLs for profile images
            $patients->getCollection()->transform(function ($patient) {
                $patient->profile_img = $patient->profile_img
                    ? asset($patient->profile_img)
                    : asset('default-profile.jpg');
                return $patient;
            });

            return response()->json([
                'isSuccess' => true,
                'message' => $patients->isEmpty()
                    ? 'No patients found.'
                    : 'Patients retrieved successfully.',
                'data' => $patients->items(),
                'pagination' => [
                    'current_page' => $patients->currentPage(),
                    'per_page' => $patients->perPage(),
                    'total' => $patients->total(),
                    'last_page' => $patients->lastPage(),
                    'has_more_pages' => $patients->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve patients.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            'profile_img'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Replace old profile image if a new one is uploaded
        if ($request->hasFile('profile_img')) {
            // Delete old file if exists
            if (!empty($patient->profile_img) && file_exists(public_path($patient->profile_img))) {
                unlink(public_path($patient->profile_img));
            }
            $validated['profile_img'] = $this->saveFileToPublic($request, 'profile_img', 'patient');
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

    // Helper function to save files
    private function saveFileToPublic(Request $request, $field, $prefix)
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            $directory = public_path('hms_files');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $filename = $prefix . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $filename);

            return 'hms_files/' . $filename;
        }

        return null;
    }
}
