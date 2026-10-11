@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto py-10" x-data="{ regType: 'new_tenant' }">
    <div class="glass-panel-glow rounded-2xl p-8 shadow-2xl relative overflow-hidden">
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center mx-auto mb-4 shadow-xl shadow-blue-500/20">
                <i data-lucide="user-plus" class="w-7 h-7 text-white"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">Create Secure Account</h2>
            <p class="text-xs text-slate-400 mt-1">Multi-Tenant Organization Registration</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <!-- Registration Type Switcher -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Registration Mode</label>
                <div class="grid grid-cols-2 gap-3 p-1 rounded-xl bg-slate-900 border border-slate-800">
                    <button type="button" @click="regType = 'new_tenant'" :class="regType === 'new_tenant' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:text-white'" class="py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center space-x-2">
                        <i data-lucide="building" class="w-3.5 h-3.5"></i>
                        <span>New Organization</span>
                    </button>
                    <button type="button" @click="regType = 'join_tenant'" :class="regType === 'join_tenant' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:text-white'" class="py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center space-x-2">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        <span>Join Organization</span>
                    </button>
                </div>
                <input type="hidden" name="registration_type" :value="regType">
            </div>

            <!-- Organization Name (New Tenant) -->
            <div x-show="regType === 'new_tenant'" x-transition>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Organization / Tenant Name</label>
                <input type="text" name="organization_name" value="{{ old('organization_name') }}" placeholder="e.g. Acme Cybersecurity Corp"
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <!-- Existing Tenant Selection (Join Tenant) -->
            <div x-show="regType === 'join_tenant'" x-transition>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Select Organization</label>
                <select name="tenant_id" class="w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-blue-500">
                    <option value="">-- Choose Tenant Organization --</option>
                    @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }} ({{ $tenant->slug }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-blue-500/25 transition-all flex items-center justify-center space-x-2">
                <i data-lucide="shield" class="w-4 h-4"></i>
                <span>Register Tenant Account</span>
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            Already registered? <a href="{{ route('login') }}" class="text-blue-400 font-semibold hover:underline">Sign In</a>
        </div>
    </div>
</div>
@endsection
