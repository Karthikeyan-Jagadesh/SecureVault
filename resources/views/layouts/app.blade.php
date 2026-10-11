<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SecureVault – Cybersecurity Multi-Tenant Platform</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        vault: {
                            50: '#f0f6ff',
                            100: '#e0edff',
                            500: '#3b82f6',
                            900: '#0f172a',
                            950: '#090d16',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js & Lucide Icons CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            background-color: #090d16;
            color: #f1f5f9;
        }
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-panel-glow {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(59, 130, 246, 0.25);
            box-shadow: 0 0 25px -5px rgba(59, 130, 246, 0.15);
        }
    </style>
</head>
<body class="font-sans antialiased min-h-screen flex flex-col selection:bg-blue-500 selection:text-white" x-data="{ notifications: [] }">

    <!-- Top Navigation Header -->
    <header class="glass-panel sticky top-0 z-40 border-b border-slate-800/80 px-4 lg:px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-500 to-cyan-400 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform duration-300">
                    <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight text-white flex items-center gap-1.5">
                        Secure<span class="text-blue-500">Vault</span>
                    </span>
                    <span class="block text-[10px] font-mono tracking-widest text-slate-400 uppercase">Enterprise Malware Defense</span>
                </div>
            </a>

            @auth
            @if(Auth::user()->tenant)
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/60 border border-slate-700/60 text-xs font-medium text-slate-300">
                <i data-lucide="building" class="w-3.5 h-3.5 text-blue-400"></i>
                <span>{{ Auth::user()->tenant->name }}</span>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    {{ Auth::user()->tenant->formatted_used }} / {{ Auth::user()->tenant->formatted_quota }}
                </span>
            </div>
            @endif
            @endauth
        </div>

        @auth
        <div class="flex items-center space-x-3">
            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center space-x-1 mr-4">
                <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    <i data-lucide="folder-git-2" class="w-4 h-4 inline-block mr-1.5"></i> File Manager
                </a>

                @if(Auth::user()->isTenantAdmin())
                <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 inline-block mr-1.5"></i> Security Center
                </a>

                <a href="{{ route('admin.quarantine') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors relative {{ request()->routeIs('admin.quarantine') ? 'bg-purple-600/20 text-purple-400 border border-purple-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    <i data-lucide="shield-alert" class="w-4 h-4 inline-block mr-1.5 text-purple-400"></i> Quarantine
                    @php $qCount = \App\Models\QuarantineFile::withoutGlobalScopes()->where('resolution_status', 'quarantined')->count(); @endphp
                    @if($qCount > 0)
                    <span class="absolute -top-1 -right-1 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-purple-500 text-white animate-pulse">
                        {{ $qCount }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('admin.audit-logs') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('admin.audit-logs') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                    <i data-lucide="file-text" class="w-4 h-4 inline-block mr-1.5"></i> Audit Logs
                </a>
                @endif
            </nav>

            <!-- User Menu Pill -->
            <div class="flex items-center gap-3 pl-3 border-l border-slate-800">
                <div class="text-right hidden sm:block">
                    <span class="block text-sm font-semibold text-white leading-tight">{{ Auth::user()->name }}</span>
                    <span class="block text-xs text-slate-400 font-mono">
                        {{ Auth::user()->role ? Auth::user()->role->display_name : 'User' }}
                    </span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout safely" class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-rose-500/20 hover:text-rose-400 border border-slate-700/60 hover:border-rose-500/40 text-slate-400 transition-all">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </header>

    <!-- Global Toast Alerts -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 lg:px-8 py-6">
        @if(session('success'))
        <div class="mb-6 p-4 rounded-xl glass-panel border-emerald-500/40 bg-emerald-950/40 text-emerald-300 flex items-start space-x-3 shadow-lg shadow-emerald-500/10">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <h4 class="font-bold text-sm text-emerald-200">Operation Verified</h4>
                <p class="text-xs text-emerald-300/90 mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('malware_alert'))
        <div class="mb-6 p-4 rounded-xl glass-panel border-rose-500/50 bg-rose-950/60 text-rose-200 flex items-start space-x-3 shadow-xl shadow-rose-500/20 animate-pulse">
            <i data-lucide="shield-alert" class="w-6 h-6 text-rose-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <h4 class="font-extrabold text-sm text-rose-100 uppercase tracking-wider flex items-center gap-2">
                    <span>SECURITY INCIDENT – MALWARE DETECTED</span>
                    <span class="px-2 py-0.5 rounded bg-rose-500 text-white text-[10px]">ISOLATED</span>
                </h4>
                <p class="text-xs text-rose-200 mt-1 font-mono">{{ session('malware_alert') }}</p>
            </div>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-6 p-4 rounded-xl glass-panel border-amber-500/40 bg-amber-950/40 text-amber-300 flex items-start space-x-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <h4 class="font-bold text-sm text-amber-200">Security Exception</h4>
                <ul class="text-xs text-amber-300/90 mt-1 list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="font-mono text-slate-400">SecureVault v2.4 Enterprise Guard Active</span>
            </div>
            <p>© 2026 SecureVault Multi-Tenant Platform • Cyber Security Academic Project</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
    @yield('scripts')
</body>
</html>
