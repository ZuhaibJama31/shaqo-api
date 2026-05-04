<?php

namespace App\Http\Controllers;

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

    /*
    | =========================
    | CHANGE PASSWORD
    | =========================
    */
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6'
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password is incorrect'
            ]);
        }

        $user->update([
            'password' => Hash::make($data['new_password'])
        ]);

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }

    /*
    | =========================
    | LOGOUT
    | =========================
    */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /*
| =========================
| UPDATE USER PROFILE
| =========================
*/
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