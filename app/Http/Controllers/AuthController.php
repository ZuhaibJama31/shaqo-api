<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client as TwilioClient;

class AuthController extends Controller
{
    public function sendCode(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string'
        ]);

        $twilio = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $twilio->verify->v2->services(
            config('services.twilio.verify_sid')
        )->verifications
        ->create($data['phone'], 'sms');

        return response()->json([
            'message' => 'Verification code sent successfully'
        ]);
    }

    public function verifyCode(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string'
        ]);

        $twilio = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $result = $twilio->verify->v2->services(
            config('services.twilio.verify_sid')
        )->verificationChecks
        ->create([
            'to'   => $data['phone'],
            'code' => $data['code']
        ]);

        if ($result->status !== 'approved') {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 400);
        }

        return response()->json([
            'message' => 'Phone verified successfully'
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'phone'       => 'required|string|unique:users,phone',
            'password'    => 'required|string|min:6',
            'role'        => 'required|in:client,worker,admin',
            'city'        => 'nullable|string',
            'category_id' => 'required_if:role,worker|nullable|exists:categories,id',
        ]);

        // Secure creation with a DB Transaction
        $user = DB::transaction(function () use ($data) {
            
            $user = User::create([
                'name'     => $data['name'],
                'phone'    => $data['phone'],
                'password' => Hash::make($data['password']),
                'role'     => $data['role'],
                'city'     => $data['city'] ?? null,
            ]);

            if ($data['role'] === 'client') {
                $user->client()->create([]);
            }

            if ($data['role'] === 'worker') {
                $user->worker()->create([
                    'category_id'  => $data['category_id'],
                    'hourly_rate'  => 0,
                    'is_available' => true,
                    'rating'       => 0,
                ]);
            }

            return $user;
        });

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
            'phone'    => 'required|string',
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

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load('worker.category')
        );
    }
}
