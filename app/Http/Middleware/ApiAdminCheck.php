<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiAdminCheck
{
    /**
     * Handle admin role checking for API requests.
     * Usage: middleware('api.admin.check')
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Check if user has admin role
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden. Only administrators can access this resource.'
            ], 403);
        }

        // Check if client is active (if applicable)
        if (isset($request->user()->is_active) && !$request->user()->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.'
            ], 403);
        }

        return $next($request);
    }
}
