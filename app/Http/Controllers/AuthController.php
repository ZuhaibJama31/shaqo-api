<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;



class AuthController extends Controller
{
    /**
     * Register a new user
     * POST /api/register
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'phone'    => 'required_if:role,client,worker|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:client,worker,admin',
            'city'     => 'nullable|string',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'city'     => $data['city'] ?? null,
        ]);

        $token = $user->createToken('app-token')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required_if:role,client,worker|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $data['phone'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Phone number or password is incorrect.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('app-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get the current authenticated user
     * GET /api/me
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->load('worker.category'));
    }
}
