<?php

namespace App\Http\Controllers;

use App\Models\Specialization;
use Illuminate\Http\Request;
use Exception;

class SpecializationController extends Controller
{
    /**
     * ✅ Create a new specialization
     */
    public function createSpecialization(Request $request)
    {
        try {
            $validated = $request->validate([
                'specialization_name' => 'required|string|max:255',
                'description' => 'required|string',
                'common_conditions' => 'required|array|min:1',
                'common_conditions.*' => 'string|max:255',
            ]);

            // Convert array to comma-separated string before saving
            $validated['common_conditions'] = implode(', ', $validated['common_conditions']);

            $specialization = Specialization::create($validated);

            return response()->json([
                'isSuccess' => true,
                'message' => 'Specialization created successfully!',
                'data' => $specialization,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to create specialization.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ✅ Retrieve all specializations
     */
    public function getSpecializations()
    {
        try {
            $specializations = Specialization::orderBy('created_at', 'desc')->get();

            if ($specializations->isEmpty()) {
                return response()->json([
                    'isSuccess' => true,
                    'message' => 'No specializations found.',
                    'data' => [],
                ]);
            }

            // Convert comma-separated string back to array
            $specializations->transform(function ($item) {
                $item->common_conditions = array_map('trim', explode(',', $item->common_conditions));
                return $item;
            });

            return response()->json([
                'isSuccess' => true,
                'message' => 'Specializations retrieved successfully.',
                'data' => $specializations,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve specializations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ✅ Retrieve a single specialization by ID
     */
    public function getSpecializationById($id)
    {
        try {
            $specialization = Specialization::find($id);

            if (!$specialization) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Specialization not found.',
                ], 404);
            }

            // Decode common_conditions into an array
            $specialization->common_conditions = array_map('trim', explode(',', $specialization->common_conditions));

            return response()->json([
                'isSuccess' => true,
                'message' => 'Specialization retrieved successfully.',
                'data' => $specialization,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve specialization.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ✅ Update an existing specialization
     */
    public function updateSpecialization(Request $request, $id)
    {
        try {
            $specialization = Specialization::find($id);

            if (!$specialization) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Specialization not found.',
                ], 404);
            }

            $validated = $request->validate([
                'specialization_name' => 'required|string|max:255',
                'description' => 'required|string',
                'common_conditions' => 'required|array|min:1',
                'common_conditions.*' => 'string|max:255',
            ]);

            // Convert array to comma-separated string
            $validated['common_conditions'] = implode(', ', $validated['common_conditions']);

            $specialization->update($validated);

            return response()->json([
                'isSuccess' => true,
                'message' => 'Specialization updated successfully.',
                'data' => $specialization,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to update specialization.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    //TEST

    /**
     * ✅ Delete a specialization
     */
    public function deleteSpecialization($id)
    {
        try {
            $specialization = Specialization::find($id);

            if (!$specialization) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Specialization not found.',
                ], 404);
            }

            $specialization->delete();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Specialization deleted successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to delete specialization.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
