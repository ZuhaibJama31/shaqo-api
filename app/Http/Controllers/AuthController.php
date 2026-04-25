<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client as TwilioClient;

class AuthController extends Controller
{
    /**
     * Send OTP Code
     * POST /api/send-code
     */
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

    /**
     * Verify OTP Code
     * POST /api/verify-code
     */
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
            'name'     => 'required|string|max:100',
            'phone'    => 'required|string|unique:users,phone',
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


        if ($data['role'] == "client") {
            CLient::create([
                'user_id' => $user->id
            ]);
        }

        
        if ($data['role'] == "worker") {
            Worker::create([
                'user_id' => $user->id
            ]);
        }

        $token = $user->createToken('app-token')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    /**
     * Login
     * POST /api/login
     */
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

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Current User
     */
    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load('worker.category')
        );
    }
}