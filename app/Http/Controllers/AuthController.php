<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\Auth as FirebaseAuth;

class AuthController extends Controller
{
    private function formatPhone($phone)
    {
        return preg_replace('/^(\+252|00252)/', '', $phone);
    }

    
    public function firebaseAuth(Request $request, FirebaseAuth $firebaseAuth)
    {
        $data = $request->validate([
            'idToken' => 'required|string',
            'name'    => 'nullable|string|max:255',
            'city'    => 'nullable|string|max:255',
        ]);

        try {
            // ✅ Verify Firebase token
            $verifiedIdToken = $firebaseAuth->verifyIdToken($data['idToken']);
            $uid = $verifiedIdToken->claims()->get('sub');

            // ✅ Get Firebase user
            $firebaseUser = $firebaseAuth->getUser($uid);
            $phone = $firebaseUser->phoneNumber;

            if (!$phone) {
                return response()->json([
                    'message' => 'Phone number not found in Firebase'
                ], 400);
            }

            $phone = $this->formatPhone($phone);

            // ✅ Find or create user
            $user = User::where('phone', $phone)->first();

            if (!$user) {
                $user = User::create([
                    'name'         => $data['name'] ?? 'User',
                    'phone'        => $phone,
                    'password'     => Hash::make(str()->random(16)),
                    'role'         => 'client',
                    'city'         => $data['city'] ?? null,
                    'firebase_uid' => $uid,
                ]);
            }

            // ✅ Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Authenticated successfully',
                'token'   => $token,
                'user'    => $user,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Invalid Firebase token',
                'error'   => $e->getMessage(),
            ], 401);
        }
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

    
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices'
        ]);
    }

    
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['city'])) {
            $user->city = $data['city'];
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user
        ]);
    }
}