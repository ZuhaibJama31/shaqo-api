<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\v1\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceTokenController extends Controller
{
    /**
     * Store or update the user's device token for push notifications.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string|max:255',
            ]);

            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Update the expo token
            $user->expo_token = $request->token;
            $user->save();

            Log::info('Push token saved', [
                'user_id' => $user->id,
                'token' => $request->token,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token saved successfully',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to save push token', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save token',
            ], 500);
        }
    }

    /**
     * Remove the device token (on logout).
     */
    public function destroy(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user) {
                $user->expo_token = null;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Token removed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove push token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove token',
            ], 500);
        }
    }
}