<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    /**
     * ✅ Get all medical records with related patient and doctor info
     */
    public function getMedicalRecords()
    {
        $records = MedicalRecord::with(['patient', 'doctor'])->get();

        return response()->json([
            'isSuccess' => true,
            'data' => $records
        ]);
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

            $record = MedicalRecord::create($validated);

            return response()->json([
                'isSuccess' => true,
                'message'   => 'Medical record created successfully!',
                'data'      => $record
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message'   => 'Failed to create medical record.',
                'error'     => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Get a specific medical record by ID
     */
    public function getMedicalRecordById($id)
    {
        $record = MedicalRecord::with(['patient', 'doctor'])->findOrFail($id);

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
        $record = MedicalRecord::findOrFail($id);

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
     * ✅ Delete a specific medical record
     */
    public function deleteMedicalRecord($id)
    {
        $record = MedicalRecord::findOrFail($id);
        $record->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Medical record deleted successfully.'
        ]);
    }
}
