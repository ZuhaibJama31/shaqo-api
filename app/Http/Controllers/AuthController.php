<?php

namespace App\Http\Controllers;

use App\Rules\SomaliPhone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client as TwilioClient;

class AuthController extends Controller
{
    /**
     * Helper to standardize phone numbers (removes +252 or 00252)
     */
    private function formatPhone($phone)
    {
        return preg_replace('/^(\+252|00252)/', '', $phone);
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', new SomaliPhone]
        ]);

        $twilio = new TwilioClient(config('services.twilio.sid'), config('services.twilio.token'));

        // Twilio usually requires the full E.164 format (+252...)
        $fullPhone = '+' . preg_replace('/^\+?/', '', $request->phone);

        $twilio->verify->v2->services(config('services.twilio.verify_sid'))
            ->verifications
            ->create($fullPhone, 'sms');

        return response()->json(['message' => 'Verification code sent successfully']);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', new SomaliPhone],
            'code'  => 'required|string'
        ]);

        $twilio = new TwilioClient(config('services.twilio.sid'), config('services.twilio.token'));
        $fullPhone = '+' . preg_replace('/^\+?/', '', $request->phone);

        $result = $twilio->verify->v2->services(config('services.twilio.verify_sid'))
            ->verificationChecks
            ->create(['to' => $fullPhone, 'code' => $request->code]);

        if ($result->status !== 'approved') {
            return response()->json(['message' => 'Invalid verification code'], 400);
        }

        return response()->json(['message' => 'Phone verified successfully']);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'phone'    => ['required', 'string', 'unique:users,phone', new SomaliPhone],
            'password' => 'required|string|min:6',
            'city'     => 'nullable|string', // Added to validation
        ]);

        $user = DB::transaction(function () use ($data) {
            return User::create([
                'name'     => $data['name'],
                'phone'    => $this->formatPhone($data['phone']),
                'password' => Hash::make($data['password']),
                'role'     => 'client',
                'city'     => $data['city'] ?? null,
            ]);
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
            'phone'    => ['required', 'string', new SomaliPhone],
            'password' => 'required|string',
        ]);

        // Clean input to match database format
        $cleanPhone = $this->formatPhone($data['phone']);
        $user = User::where('phone', $cleanPhone)->first();

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

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        if (Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'New password must be different.'], 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load('worker.category', 'client')
        );
    }
}
