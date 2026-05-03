<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function normalizePhone($phone)
    {
        $phone = trim($phone);

        // Somalia format fix: 0XXXXXXXXX → +252XXXXXXXXX
        if (preg_match('/^0/', $phone)) {
            return '+252' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * REGISTER USER
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'name'     => 'required|string',
            'city'     => 'nullable|string',
            'role'     => 'required|in:client,worker'
        ]);

        $phone = $this->normalizePhone($data['phone']);

        $user = User::create([
            'phone'    => $phone,
            'name'     => $data['name'],
            'city'     => $data['city'] ?? null,
            'role'     => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token'   => $token,
            'user'    => $user
        ], 201);
    }

    /**
     * LOGIN WITH PASSWORD
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string'
        ]);

        $phone = $this->normalizePhone($data['phone']);

        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user
        ]);
    }

    
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string|min:6|confirmed'
        ]);

        $phone = $this->normalizePhone($data['phone']);

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update([
            'password' => Hash::make($data['password'])
        ]);

        return response()->json([
            'message' => 'Password reset successful'
        ]);
    }

    /**
     * GET CURRENT USER
     */
    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load('worker.category', 'client')
        );
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * UPDATE PROFILE
     */
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        $user = $request->user();
        $user->update(array_filter($data));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user
        ]);
    }
}