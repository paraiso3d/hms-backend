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
     *  Register a new doctor
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
                'about'               => 'nullable|string',
                'university_graduated' => 'nullable|string|max:255',
                'available_days'      => 'required|array|min:1',
                'available_days.*'    => 'string|max:50',
                'profile_img'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);


            // 📁 Save uploaded profile image
            $profilePath = $this->saveFileToPublic($request, 'profile_img', 'doctor');

            $doctor = Doctor::create([
                'doctor_name'         => $validated['doctor_name'],
                'email'               => $validated['email'],
                'password'            => Hash::make($validated['password']),
                'specialization_id'   => $validated['specialization_id'],
                'years_of_experience' => $validated['years_of_experience'],
                'consultation_fee'    => $validated['consultation_fee'],
                'qualifications'      => $validated['qualifications'],
                'about'               => $validated['about'] ?? null,
                'university_graduated' => $validated['university_graduated'] ?? null,
                'role'                => 'Doctor',
                'profile_img'         => $profilePath,
                'is_archived'         => 0,
            ]);


            // 🗓️ Store available days
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
                'password'            => 'nullable|string|min:6|confirmed',
                'specialization_id'   => 'required|integer|exists:specializations,id',
                'years_of_experience' => 'required|integer|min:0',
                'consultation_fee'    => 'required|numeric|min:0',
                'qualifications'      => 'required|string|max:255',
                'about'               => 'nullable|string',
                'university_graduated' => 'nullable|string|max:255',
                'available_days'      => 'required|array|min:1',
                'available_days.*'    => 'string|max:50',
                'profile_img'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $updateData = [
                'doctor_name'         => $validated['doctor_name'],
                'email'               => $validated['email'],
                'specialization_id'   => $validated['specialization_id'],
                'years_of_experience' => $validated['years_of_experience'],
                'consultation_fee'    => $validated['consultation_fee'],
                'qualifications'      => $validated['qualifications'],
                'about'               => $validated['about'] ?? $doctor->about,
                'university_graduated' => $validated['university_graduated'] ?? $doctor->university_graduated,
            ];


            // 🔐 Update password if provided
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            // 🖼️ Update profile image if new one uploaded
            if ($request->hasFile('profile_img')) {
                // Optional: delete old image if it exists
                if ($doctor->profile_img && file_exists(public_path($doctor->profile_img))) {
                    unlink(public_path($doctor->profile_img));
                }

                $updateData['profile_img'] = $this->saveFileToPublic($request, 'profile_img', 'doctor');
            }

            $doctor->update($updateData);

            // 🔁 Refresh available days
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
     * Retrieve all active doctors 
     */
    public function getDoctors(Request $request)
    {
        try {
            $search  = $request->input('search');
            $perPage = $request->input('per_page', 10); // Default 10 per page

            $query = Doctor::with(['specialization', 'availableDays'])
                ->where('is_archived', 0)
                ->orderBy('created_at', 'desc');

            // 🔍 Search logic
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('doctor_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhereHas('specialization', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // 📄 Pagination
            $doctors = $query->paginate($perPage);

            // 🖼️ Add full asset URL for profile_img
            $doctors->getCollection()->transform(function ($doctor) {
                $doctor->profile_img = $doctor->profile_img
                    ? asset($doctor->profile_img)
                    : asset('default-profile.png'); // Optional fallback
                return $doctor;
            });

            return response()->json([
                'isSuccess' => true,
                'message' => $doctors->isEmpty()
                    ? 'No doctors found.'
                    : 'Doctors retrieved successfully.',
                'data' => $doctors->items(),
                'pagination' => [
                    'current_page' => $doctors->currentPage(),
                    'per_page' => $doctors->perPage(),
                    'total' => $doctors->total(),
                    'last_page' => $doctors->lastPage(),
                    'has_more_pages' => $doctors->hasMorePages(),
                ],
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
     * Retrieve a single doctor by ID
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

            // 🖼️ Add full asset URL for profile_img
            $doctor->profile_img = $doctor->profile_img
                ? asset($doctor->profile_img)
                : asset('default-profile.png'); // optional fallback

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


    public function getMyDoctorProfile()
    {
        try {
            $doctor = auth()->user();

            // 🧠 Check if the logged-in user is actually a doctor
            if (!$doctor || $doctor->role !== 'Doctor') {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'Unauthorized access.',
                ], 401);
            }

            // 🔍 Fetch the full doctor record with relationships
            $doctorData = Doctor::with(['specialization', 'availableDays'])
                ->where('id', $doctor->id)
                ->where('is_archived', 0)
                ->first();

            if (!$doctorData) {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'Doctor profile not found.',
                ], 404);
            }

            // 🖼️ Add full URL for profile image (with fallback)
            $doctorData->profile_img = $doctorData->profile_img
                ? asset($doctorData->profile_img)
                : asset('default-profile.png');

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Doctor profile retrieved successfully.',
                'data'      => $doctorData,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to retrieve doctor profile.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }



    public function updateMyDoctorProfile(Request $request)
    {
        try {
            $doctor = auth()->user();

            if (!$doctor || $doctor->role !== 'Doctor') {
                return response()->json([
                    'isSuccess' => false,
                    'message'   => 'Unauthorized access.',
                ], 401);
            }

            $validated = $request->validate([
                'doctor_name'          => 'sometimes|string|max:255',
                'email'                => 'sometimes|email|unique:doctors,email,' . $doctor->id,
                'password'             => 'sometimes|string|min:6|confirmed',
                'specialization_id'    => 'sometimes|integer|exists:specializations,id',
                'years_of_experience'  => 'sometimes|integer|min:0',
                'consultation_fee'     => 'sometimes|numeric|min:0',
                'qualifications'       => 'sometimes|string|max:255',
                'about'                => 'sometimes|string',
                'university_graduated' => 'sometimes|string|max:255',
                'available_days'       => 'sometimes|array|min:1',
                'available_days.*'     => 'string|max:50',
                'profile_img'          => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $updateData = [
                'doctor_name'          => $validated['doctor_name'] ?? $doctor->doctor_name,
                'email'                => $validated['email'] ?? $doctor->email,
                'specialization_id'    => $validated['specialization_id'] ?? $doctor->specialization_id,
                'years_of_experience'  => $validated['years_of_experience'] ?? $doctor->years_of_experience,
                'consultation_fee'     => $validated['consultation_fee'] ?? $doctor->consultation_fee,
                'qualifications'       => $validated['qualifications'] ?? $doctor->qualifications,
                'about'                => $validated['about'] ?? $doctor->about,
                'university_graduated' => $validated['university_graduated'] ?? $doctor->university_graduated,
            ];

            // 🔐 Update password if provided
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            // 🖼️ Handle new profile image
            if ($request->hasFile('profile_img')) {
                if ($doctor->profile_img && file_exists(public_path($doctor->profile_img))) {
                    @unlink(public_path($doctor->profile_img));
                }

                $file = $request->file('profile_img');
                $filename = 'doctor_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('hms_files/doctor'), $filename);
                $updateData['profile_img'] = 'hms_files/doctor/' . $filename;
            }

            // ✅ Update doctor
            Doctor::where('id', $doctor->id)->update($updateData);

            // 🔁 Update available days if provided
            if (isset($validated['available_days'])) {
                $doctor->availableDays()->delete();
                foreach ($validated['available_days'] as $day) {
                    $doctor->availableDays()->create(['day_of_week' => $day]);
                }
            }

            $updatedDoctor = Doctor::with(['specialization', 'availableDays'])->find($doctor->id);
            $updatedDoctor->profile_img = $updatedDoctor->profile_img
                ? asset($updatedDoctor->profile_img)
                : asset('default-profile.png');

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Doctor profile updated successfully!',
                'data'      => $updatedDoctor,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to update doctor profile.',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * Get appointments for the logged-in doctor
     */
    public function getMyAppointments(Request $request)
    {
        try {
            $doctor = auth()->user();

            if (!$doctor || $doctor->role !== 'Doctor') {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            $search  = $request->input('search');
            $perPage = $request->input('per_page', 10);

            $query = Appointment::where('doctor_id', $doctor->id)
                ->where('is_archived', 0)
                ->with(['patient'])
                ->orderBy('appointment_date', 'desc');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('patient', function ($sub) use ($search) {
                        $sub->where('patient_name', 'like', "%{$search}%");
                    })
                        ->orWhere('appointment_date', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $appointments = $query->paginate($perPage);

            return response()->json([
                'isSuccess' => true,
                'message' => $appointments->isEmpty()
                    ? 'No appointments found for this doctor.'
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
                'message' => 'Failed to fetch appointments.',
                'error' => $e->getMessage(),
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
     *  Soft delete doctor
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

    //HELPER FUNCTION TO SAVE FILES
    private function saveFileToPublic(Request $request, $field, $prefix)
    {
        // Check if file exists in the request
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            // Create directory inside /public if not exists
            $directory = public_path('hms_files');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Generate unique filename with prefix
            $filename = $prefix . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Move uploaded file to /public/pos_files
            $file->move($directory, $filename);

            // Return relative path (to store in database)
            return 'hms_files/' . $filename;
        }

        return null;
    }
}
