<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private function formatPhone($phone)
    {
        return preg_replace('/^(\+252|00252)/', '', $phone);
    }

    /**
     * Firebase verified login/register
     */
    public function firebaseAuth(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'name'  => 'nullable|string',
            'city'  => 'nullable|string',
        ]);

        $phone = $this->formatPhone($data['phone']);

        // check if user exists
        $user = User::where('phone', $phone)->first();

        // if not exists → create user (REGISTER FLOW)
        if (!$user) {
            $user = User::create([
                'name'     => $data['name'] ?? 'User',
                'phone'    => $phone,
                'password' => Hash::make(str()->random(16)), // no password needed
                'role'     => 'client',
                'city'     => $data['city'] ?? null,
            ]);
        }

        // create token
        $token = $user->createToken('firebase-auth')->plainTextToken;

        return response()->json([
            'message' => 'Authenticated successfully',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load('worker.category', 'client')
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}