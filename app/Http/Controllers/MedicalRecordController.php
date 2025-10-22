<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Exception;

class MedicalRecordController extends Controller
{
    /**
     * ✅ Get all medical records (excluding archived)
     */
    public function getMedicalRecords(Request $request)
    {
        try {
            $search  = $request->input('search');
            $perPage = $request->input('per_page', 10);

            $query = MedicalRecord::with(['patient', 'doctor'])
                ->where('is_archived', 0)
                ->orderBy('created_at', 'desc');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('patient', function ($sub) use ($search) {
                        $sub->where('patient_name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('doctor', function ($sub) use ($search) {
                            $sub->where('doctor_name', 'like', "%{$search}%");
                        })
                        ->orWhere('diagnosis', 'like', "%{$search}%")
                        ->orWhere('treatment', 'like', "%{$search}%")
                        ->orWhere('record_date', 'like', "%{$search}%");
                });
            }

            $records = $query->paginate($perPage);

            return response()->json([
                'isSuccess' => true,
                'message' => $records->isEmpty()
                    ? 'No medical records found.'
                    : 'Medical records retrieved successfully.',
                'data' => $records->items(),
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'per_page' => $records->perPage(),
                    'total' => $records->total(),
                    'last_page' => $records->lastPage(),
                    'has_more_pages' => $records->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve medical records.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * ✅ Create a new medical record
     */
    public function createMedicalRecord(Request $request)
    {
        try {
            $validated = $request->validate([
                'appointment_id'     => 'required|exists:appointments,id',
                'patient_id'         => 'required|exists:patients,id',
                'doctor_id'          => 'required|exists:doctors,id',
                'blood_pressure'     => 'nullable|string|max:50',
                'temperature'        => 'nullable|string|max:50',
                'heart_rate'         => 'nullable|string|max:50',
                'weight'             => 'nullable|string|max:50',
                'chief_complaint'    => 'nullable|string',
                'diagnosis'          => 'required|string',
                'treatment'          => 'nullable|string',
                'treatment_plan'     => 'nullable|string',
                'prescription'       => 'nullable|string',
                'notes'              => 'nullable|string',
                'follow_up_required' => 'boolean',
                'record_date'        => 'required|date',
            ]);

            $validated['is_archived'] = 0; // always default to not archived

            $record = MedicalRecord::create($validated);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Medical record created successfully!',
                'data'      => $record
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to create medical record.',
                'error'     => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Get a specific medical record by ID (excluding archived)
     */
    public function getMedicalRecordById($id)
    {
        $record = MedicalRecord::with(['patient', 'doctor'])
            ->where('id', $id)
            ->where('is_archived', 0)
            ->first();

        if (!$record) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Medical record not found or archived.'
            ], 404);
        }

        return response()->json([
            'isSuccess' => true,
            'data' => $record
        ]);
    }

    /**
     * ✅ Update a specific medical record
     */
    public function updateMedicalRecord(Request $request, $id)
    {
        $record = MedicalRecord::where('id', $id)
            ->where('is_archived', 0)
            ->first();

        if (!$record) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Medical record not found or archived.'
            ], 404);
        }

        $validated = $request->validate([
            'diagnosis' => 'sometimes|required|string',
            'treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'date_recorded' => 'sometimes|required|date',
        ]);

        $record->update($validated);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Medical record updated successfully.',
            'data' => $record
        ]);
    }

    /**
     * ✅ Archive (soft delete) a medical record
     */
    public function deleteMedicalRecord($id)
    {
        try {
            $record = MedicalRecord::find($id);

            if (!$record) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Medical record not found.',
                ], 404);
            }

            $record->is_archived = 1;
            $record->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Medical record archived successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to archive medical record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
