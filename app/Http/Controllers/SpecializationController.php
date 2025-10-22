<?php

namespace App\Http\Controllers;

use App\Models\Specialization;
use Illuminate\Http\Request;
use Exception;

class SpecializationController extends Controller
{
    /**
     * 🩺 Create a new specialization
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

            // Convert array to comma-separated string
            $validated['common_conditions'] = implode(', ', $validated['common_conditions']);
            $validated['is_archived'] = 0; // default active

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
     * 📋 Retrieve all active (non-archived) specializations
     */
    public function getSpecializations(Request $request)
    {
        try {
            $search  = $request->input('search');
            $perPage = $request->input('per_page', 10); // Default 10 per page

            $query = Specialization::where('is_archived', 0)
                ->orderBy('created_at', 'desc');

            // 🔍 Search by specialization name or common conditions
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('common_conditions', 'like', "%{$search}%");
                });
            }

            // 📄 Apply pagination
            $specializations = $query->paginate($perPage);

            // 🧩 Convert comma-separated string to array for each specialization
            $specializations->getCollection()->transform(function ($item) {
                $item->common_conditions = array_map('trim', explode(',', $item->common_conditions));
                return $item;
            });

            return response()->json([
                'isSuccess' => true,
                'message'   => $specializations->isEmpty()
                    ? 'No specializations found.'
                    : 'Specializations retrieved successfully.',
                'data'      => $specializations,
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
     * 🔍 Retrieve a single specialization by ID (only if not archived)
     */
    public function getSpecializationById($id)
    {
        try {
            $specialization = Specialization::where('id', $id)
                ->where('is_archived', 0)
                ->first();

            if (!$specialization) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Specialization not found or archived.',
                ], 404);
            }

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
     * ✏️ Update an existing specialization (only if active)
     */
    public function updateSpecialization(Request $request, $id)
    {
        try {
            $specialization = Specialization::where('id', $id)
                ->where('is_archived', 0)
                ->first();

            if (!$specialization) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Specialization not found or archived.',
                ], 404);
            }

            $validated = $request->validate([
                'specialization_name' => 'required|string|max:255',
                'description' => 'required|string',
                'common_conditions' => 'required|array|min:1',
                'common_conditions.*' => 'string|max:255',
            ]);

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

    /**
     * 🗑️ Soft delete (archive) a specialization
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

            $specialization->is_archived = 1;
            $specialization->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Specialization archived successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to archive specialization.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
