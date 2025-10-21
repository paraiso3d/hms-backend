<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Exception;

class AuthController extends Controller
{
    /**
     * Universal Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'nullable|string',
            'email' => 'nullable|string|email',
            'password' => 'required|string',
        ]);

        $user = null;
        $role = null;

        /**
         *  Admin Login (users table)
         */
        if ($request->username) {
            $user = User::where('username', $request->username)->first();
            $role = 'Admin';
        }

        /**
         *  Doctor Login (doctors table)
         */
        if (!$user && $request->email) {
            $user = Doctor::where('email', $request->email)->first();
            $role = 'Doctor';
        }

        /**
         * 🧑‍🤝‍🧑Patient Login (patients table)
         */
        if (!$user && $request->email) {
            $user = Patient::where('email', $request->email)->first();
            $role = 'Patient';
        }

        // If no matching user found
        if (!$user || !$user->password || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Create a Sanctum token (per model type)
        $token = $user->createToken($role . '_auth_token')->plainTextToken;

        return response()->json([
            'isSuccess' => true,
            'message' => 'Login successful.',
            'role' => $role,
            'user' => $this->formatUserResponse($user, $role),
            'token' => $token,
        ]);
    }

    /**
     *  Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Logout successful.',
        ]);
    }

    /**
     *  Format response data depending on user type
     */
    private function formatUserResponse($user, $role)
    {
        switch ($role) {
            case 'Admin':
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => 'Admin',
                ];

            case 'Doctor':
                return [
                    'id' => $user->id,
                    'doctor_name' => $user->doctor_name,
                    'email' => $user->email,
                    'role' => 'Doctor',
                    'specialization_id' => $user->specialization_id,
                ];

            case 'Patient':
                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role' => 'Patient',
                ];

            default:
                return [];
        }
    }
}
