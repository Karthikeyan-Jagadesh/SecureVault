<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                AuditLogService::log('LOGIN_FAILED_DEACTIVATED', 'auth', 'User', $user->id, ['email' => $credentials['email']], 'warning');
                return back()->withErrors(['email' => 'Your account is deactivated.']);
            }

            $user->update(['last_login_at' => now()]);

            AuditLogService::log('LOGIN_SUCCESS', 'auth', 'User', $user->id, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : 'none',
            ], 'info', $user->tenant_id, $user->id);

            return redirect()->intended(route('dashboard'));
        }

        AuditLogService::log('LOGIN_FAILED', 'auth', 'User', null, [
            'attempted_email' => $credentials['email'],
            'reason' => 'Invalid credentials',
        ], 'warning');

        return back()->withErrors([
            'email' => 'The provided credentials do not match our security records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $tenants = Tenant::where('status', 'active')->get();
        return view('auth.register', compact('tenants'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'registration_type' => ['required', 'in:join_tenant,new_tenant'],
            'tenant_id' => ['required_if:registration_type,join_tenant', 'nullable', 'exists:tenants,id'],
            'organization_name' => ['required_if:registration_type,new_tenant', 'nullable', 'string', 'max:255'],
        ]);

        $tenantId = null;
        $roleName = 'tenant_user';

        if ($validated['registration_type'] === 'new_tenant') {
            $tenant = Tenant::create([
                'name' => $validated['organization_name'],
                'slug' => Str::slug($validated['organization_name']) . '-' . Str::random(4),
                'storage_quota_bytes' => 524288000, // 500 MB default
                'storage_used_bytes' => 0,
                'status' => 'active',
            ]);
            $tenantId = $tenant->id;
            $roleName = 'tenant_admin';
        } else {
            $tenantId = $validated['tenant_id'];
        }

        $role = Role::where('name', $roleName)->first();

        $user = User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role ? $role->id : null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        Auth::login($user);

        AuditLogService::log('USER_REGISTERED', 'auth', 'User', $user->id, [
            'registration_type' => $validated['registration_type'],
            'tenant_id' => $tenantId,
            'role' => $roleName,
        ], 'info', $tenantId, $user->id);

        return redirect()->route('dashboard')->with('success', 'Welcome to SecureVault! Your secure workspace is ready.');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            AuditLogService::log('LOGOUT', 'auth', 'User', $user->id, [], 'info', $user->tenant_id, $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out safely.');
    }
}
