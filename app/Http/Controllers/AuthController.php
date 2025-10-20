<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * 🧠 Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Invalid username or password.',
            ], 401);
        }

        // Delete old tokens (optional for single-session login)
        $user->tokens()->delete();

        // Create a new Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'isSuccess' => true,
            'message' => 'Login successful.',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
            'token' => $token,
        ]);
    }

    /**
     * 🚪 Logout
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Logout successful.',
        ]);
    }
}
