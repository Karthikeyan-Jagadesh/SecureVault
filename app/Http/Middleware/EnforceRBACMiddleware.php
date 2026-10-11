<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceRBACMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$rolesOrPermissions)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated. Please contact support.']);
        }

        if (empty($rolesOrPermissions)) {
            return $next($request);
        }

        // Super Admin bypasses role restrictions
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        foreach ($rolesOrPermissions as $param) {
            if (str_starts_with($param, 'perm:')) {
                $permission = substr($param, 5);
                if ($user->hasPermission($permission)) {
                    return $next($request);
                }
            } else {
                if ($user->role && $user->role->name === $param) {
                    return $next($request);
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized. Insufficient security permissions.'], 403);
        }

        abort(403, 'Access Denied: You do not possess the required RBAC role or security permission.');
    }
}
