@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto py-12">
    <div class="glass-panel-glow rounded-2xl p-8 shadow-2xl relative overflow-hidden">
        <!-- Background Ambient Glow -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl"></div>

        <div class="text-center mb-8 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center mx-auto mb-4 shadow-xl shadow-blue-500/20">
                <i data-lucide="lock" class="w-7 h-7 text-white"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">Secure Vault Sign In</h2>
            <p class="text-xs text-slate-400 mt-1">Enterprise Multi-Tenant Authentication Portal</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5 relative z-10">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </span>
                    <input type="email" name="email" id="email_input" value="{{ old('email') }}" required autofocus
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="key-round" class="w-4 h-4"></i>
                    </span>
                    <input type="password" name="password" id="password_input" required
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm transition-all">
                </div>
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center space-x-2 cursor-pointer text-slate-300">
                    <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-blue-500">
                    <span>Remember session</span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-blue-500/25 transition-all duration-200 flex items-center justify-center space-x-2">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Authenticate & Access Vault</span>
            </button>
        </form>

        <!-- Quick Demo Credentials Selector -->
        <div class="mt-8 pt-6 border-t border-slate-800 relative z-10">
            <span class="block text-[11px] font-mono text-slate-400 uppercase tracking-widest text-center mb-3">Quick Demo Logins</span>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button onclick="fillLogin('admin@cyberguard.com', 'Password123!')" class="p-2 rounded-lg bg-slate-800/80 hover:bg-blue-600/20 border border-slate-700 hover:border-blue-500/40 text-left transition-all">
                    <span class="block font-bold text-blue-400">CyberGuard Admin</span>
                    <span class="block text-[10px] text-slate-400">Tenant Administrator</span>
                </button>

                <button onclick="fillLogin('user@cyberguard.com', 'Password123!')" class="p-2 rounded-lg bg-slate-800/80 hover:bg-emerald-600/20 border border-slate-700 hover:border-emerald-500/40 text-left transition-all">
                    <span class="block font-bold text-emerald-400">CyberGuard User</span>
                    <span class="block text-[10px] text-slate-400">Standard User</span>
                </button>

                <button onclick="fillLogin('admin@apexfintech.com', 'Password123!')" class="p-2 rounded-lg bg-slate-800/80 hover:bg-amber-600/20 border border-slate-700 hover:border-amber-500/40 text-left transition-all">
                    <span class="block font-bold text-amber-400">Apex Admin</span>
                    <span class="block text-[10px] text-slate-400">Isolated Tenant Admin</span>
                </button>

                <button onclick="fillLogin('superadmin@securevault.local', 'Password123!')" class="p-2 rounded-lg bg-slate-800/80 hover:bg-purple-600/20 border border-slate-700 hover:border-purple-500/40 text-left transition-all">
                    <span class="block font-bold text-purple-400">Super Admin</span>
                    <span class="block text-[10px] text-slate-400">System Administrator</span>
                </button>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            Need a tenant account? <a href="{{ route('register') }}" class="text-blue-400 font-semibold hover:underline">Register New Organization</a>
        </div>
    </div>
</div>

<script>
    function fillLogin(email, password) {
        document.getElementById('email_input').value = email;
        document.getElementById('password_input').value = password;
    }
</script>
@endsection
