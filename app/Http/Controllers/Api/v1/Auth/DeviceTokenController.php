<?php

namespace App\Http\Controllers\Api\v1\Auth;
use App\Http\Controllers\Api\v1\Controller;

use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
   public function store(Request $request)
{
    $request->validate([
        'token' => 'required|string'
    ]);

    $user = $request->user();

    $user->deviceTokens()->updateOrCreate(
        ['token' => $request->token],
        ['token' => $request->token]
    );

    return response()->json(['message' => 'Token saved']);
}
}
