<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows only users whose `role` column equals "client".
 * Works the same way your existing Admin middleware works.
 */
class ClientMiddleware
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // `auth:sanctum` guarantees $request->user() is an instance of App\Models\User
        $user = $request->user();

        // If a user is not logged in or the role is not "client" → 403
        if (! $user || $user->role !== 'client') {
            abort(Response::HTTP_FORBIDDEN, 'Access denied – client role required.');
        }

        return $next($request);
    }
}
