<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\v1\Controller;
use App\Models\User;
use App\Services\ExpoNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected ExpoNotificationService $expo) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required|unique:users,phone',
            'name'     => 'required',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'phone'    => $data['phone'],
            'name'     => $data['name'],
            'password' => Hash::make($data['password']),
            'role'     => 'client', // make sure role is set
        ]);

        // 🔔 Notify all admins
        $adminTokens = User::where('role', 'admin')
            ->whereNotNull('expo_token')
            ->pluck('expo_token')
            ->map(fn($t) => (string) $t)
            ->all();

        if (!empty($adminTokens)) {
            $this->expo->send(
                $adminTokens,
                '👤 New Client Registered',
                "{$user->name} just created an account.",
                ['type' => 'new_client', 'user_id' => $user->id]
            );
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully',
            'token'   => $token,
            'user'    => $user
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required',
            'password' => 'required'
        ]);

        $user = User::where('phone', $data['phone'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => 'Invalid phone or password'
            ]);
        }

        return response()->json([
            'token' => $user->createToken('auth')->plainTextToken,
            'user'  => $user
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function passwordReset(Request $request)
    {
        $data = $request->validate([
            'current_password'    => ['required', 'string'],
            'new_password'        => ['required', 'string', 'min:8', 'different:current_password'],
            'confirm_new_password' => ['required', 'same:new_password'],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match our records.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['new_password'])
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'sometimes|required|string',
            'phone' => 'sometimes|required|unique:users,phone,' . $user->id,
        ]);

        if (isset($data['name']))  $user->name  = $data['name'];
        if (isset($data['phone'])) $user->phone = $data['phone'];

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user
        ]);
    }
}