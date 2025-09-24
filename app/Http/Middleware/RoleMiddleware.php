<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $user = Auth::user();
        
        // Get user role from profile or direct relationship
        $userRole = $user->profile?->role?->value ?? $user->role?->name ?? null;
        
        // If no roles specified, just check if authenticated
        if (empty($roles)) {
            return $next($request);
        }
        
        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if (strtolower($userRole) === strtolower($role)) {
                return $next($request);
            }
        }
        
        // User doesn't have required role
        return response()->json([
            'success' => false,
            'message' => 'Insufficient permissions. Required role: ' . implode(' or ', $roles) . '. Current role: ' . ($userRole ?? 'none')
        ], 403);
    }
}