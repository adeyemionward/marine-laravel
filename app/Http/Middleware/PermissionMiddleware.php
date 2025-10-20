<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The permission required to access this route
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Super admin bypasses all permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required permission
        if (!$user->hasPermissionTo($permission, 'api')) {
            Log::warning('PERMISSION_DENIED', [
                'user_id' => $user->id,
                'email' => $user->email,
                'permission' => $permission,
                'route' => $request->route()->getName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
