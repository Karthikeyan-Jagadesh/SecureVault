<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantScopeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->tenant && $user->tenant->status === 'suspended' && !$user->isSuperAdmin()) {
                Auth::logout();
                return redirect()->route('login')->withErrors(['email' => 'Your organization tenant account has been suspended by system administrator.']);
            }
        }

        return $next($request);
    }
}
