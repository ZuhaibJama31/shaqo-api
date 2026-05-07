<?php

namespace App\Http\Controllers\Api\v1\Auth;
use App\Http\Controllers\Api\v1\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /*
    | =========================
    | REGISTER (PASSWORD ONLY)
    | =========================
    */
    public function register(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|unique:users,phone',
            'name'  => 'required',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'phone' => $data['phone'],
            'name'  => $data['name'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully',
            'token' => $token,
            'user' => $user
        ]);
    }

    /*
    | =========================
    | LOGIN (PASSWORD ONLY)
    | =========================
    */
    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required',
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
            'user' => $user
        ]);
    }

    /*
    | =========================
    | USER PROFILE
    | =========================
    */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function passwordReset(Request $request)
{
    $data = $request->validate([
        'current_password' => ['required', 'string'],
        'new_password' => ['required', 'string', 'min:8', 'different:current_password'],
        'confirm_new_password' => ['required', 'same:new_password'],
    ]);

    $user = $request->user();

    // Verify the old password
    if (!Hash::check($data['current_password'], $user->password)) {
        throw ValidationException::withMessages([
            'current_password' => ['The provided password does not match our records.'],
        ]);
    }

    // Update and save
    $user->update([
        'password' => Hash::make($data['new_password'])
    ]);

    return response()->json([
        'message' => 'Password updated successfully.'
    ]);
}

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
    
    
    public function updateProfile(Request $request)
    
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'sometimes|required|string',
            'phone' => 'sometimes|required|unique:users,phone,' . $user->id,
        ]);

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }

        $user->save();

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);
}
}