@extends('layouts.app')

@section('content')
<div class="space-y-8" x-data="liveSecurityCenter()">

    <!-- Dashboard Title & Live Connection Status Banner -->
    <div class="glass-panel-glow rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-slate-800">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Security Command Center</h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-mono bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Real-Time Monitoring Active</span>
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Multi-tenant malware detection, audit activity, and quarantine management</p>
        </div>

        <div class="flex items-center space-x-3 bg-slate-900/90 px-4 py-2 rounded-xl border border-slate-800 font-mono text-xs">
            <i data-lucide="radio" class="w-4 h-4 text-emerald-400 animate-pulse"></i>
            <span class="text-slate-300">Live SSE Socket Stream:</span>
            <span class="text-emerald-400 font-bold" x-text="streamStatus">Connecting...</span>
        </div>
    </div>

    <!-- 7 Key System Metrics Widgets -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">

        <!-- Total Users -->
        <div class="glass-panel p-4 rounded-xl border-slate-800">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-mono uppercase tracking-wider">Total Users</span>
                <i data-lucide="users" class="w-4 h-4 text-blue-400"></i>
            </div>
            <span class="text-2xl font-extrabold text-white font-mono">{{ $totalUsers }}</span>
            <span class="block text-[10px] text-slate-500 mt-1">Active Accounts</span>
        </div>

        <!-- Total Tenants -->
        <div class="glass-panel p-4 rounded-xl border-slate-800">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-mono uppercase tracking-wider">Tenants</span>
                <i data-lucide="building" class="w-4 h-4 text-indigo-400"></i>
            </div>
            <span class="text-2xl font-extrabold text-white font-mono">{{ $totalTenants }}</span>
            <span class="block text-[10px] text-slate-500 mt-1">Isolated Orgs</span>
        </div>

        <!-- Total Files -->
        <div class="glass-panel p-4 rounded-xl border-slate-800">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-mono uppercase tracking-wider">Total Files</span>
                <i data-lucide="file-text" class="w-4 h-4 text-cyan-400"></i>
            </div>
            <span class="text-2xl font-extrabold text-white font-mono" x-text="liveStats.total_files !== undefined ? liveStats.total_files : '{{ $totalFiles }}'">{{ $totalFiles }}</span>
            <span class="block text-[10px] text-slate-500 mt-1">Vault Storage</span>
        </div>

        <!-- Storage Used -->
        <div class="glass-panel p-4 rounded-xl border-slate-800">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-mono uppercase tracking-wider">Storage</span>
                <i data-lucide="hard-drive" class="w-4 h-4 text-blue-400"></i>
            </div>
            <span class="text-lg font-extrabold text-white font-mono">{{ $storagePercentage }}%</span>
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2">
                <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ min($storagePercentage, 100) }}%"></div>
            </div>
        </div>

        <!-- Safe Files -->
        <div class="glass-panel p-4 rounded-xl border-emerald-500/20 bg-emerald-950/20">
            <div class="flex items-center justify-between text-emerald-400 mb-2">
                <span class="text-[11px] font-mono uppercase tracking-wider">Safe Files</span>
                <i data-lucide="shield-check" class="w-4 h-4"></i>
            </div>
            <span class="text-2xl font-extrabold text-emerald-400 font-mono" x-text="liveStats.safe_files !== undefined ? liveStats.safe_files : '{{ $safeFiles }}'">{{ $safeFiles }}</span>
            <span class="block text-[10px] text-emerald-500 mt-1">Clean & Scanned</span>
        </div>

        <!-- Malicious Files -->
        <div class="glass-panel p-4 rounded-xl border-rose-500/20 bg-rose-950/20">
            <div class="flex items-center justify-between text-rose-400 mb-2">
                <span class="text-[11px] font-mono uppercase tracking-wider">Malware</span>
                <i data-lucide="bug" class="w-4 h-4"></i>
            </div>
            <span class="text-2xl font-extrabold text-rose-400 font-mono" x-text="liveStats.malicious_files !== undefined ? liveStats.malicious_files : '{{ $maliciousFiles }}'">{{ $maliciousFiles }}</span>
            <span class="block text-[10px] text-rose-500 mt-1">Threats Detected</span>
        </div>

        <!-- Quarantined Files -->
        <div class="glass-panel p-4 rounded-xl border-purple-500/20 bg-purple-950/20">
            <div class="flex items-center justify-between text-purple-400 mb-2">
                <span class="text-[11px] font-mono uppercase tracking-wider">Quarantined</span>
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
            </div>
            <span class="text-2xl font-extrabold text-purple-400 font-mono" x-text="liveStats.quarantined_files !== undefined ? liveStats.quarantined_files : '{{ $quarantinedFiles }}'">{{ $quarantinedFiles }}</span>
            <span class="block text-[10px] text-purple-500 mt-1">Isolated Vault</span>
        </div>
    </div>

    <!-- Malware Scanning Statistics & Recent Scans -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Scan Performance Statistics -->
        <div class="glass-panel rounded-2xl p-6 border-slate-800 space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i data-lucide="cpu" class="w-4 h-4 text-blue-400"></i> Malware Scanner Performance
            </h3>

            <div class="space-y-3 font-mono text-xs">
                <div class="flex justify-between p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                    <span class="text-slate-400">Total Scans Performed:</span>
                    <span class="text-white font-bold">{{ $scanStats['total_scans'] }}</span>
                </div>

                <div class="flex justify-between p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                    <span class="text-slate-400">Threats Stopped:</span>
                    <span class="text-rose-400 font-bold">{{ $scanStats['threats_detected'] }}</span>
                </div>

                <div class="flex justify-between p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                    <span class="text-slate-400">Avg Scan Latency:</span>
                    <span class="text-blue-400 font-bold">{{ $scanStats['avg_duration_ms'] }} ms</span>
                </div>
            </div>
        </div>

        <!-- Recent Malware Scans Log Feed -->
        <div class="glass-panel rounded-2xl p-6 lg:col-span-2 border-slate-800">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                <i data-lucide="activity" class="w-4 h-4 text-blue-400"></i> Recent File Scan History
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono">
                    <thead class="bg-slate-900/90 text-slate-400 uppercase border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3">File ID</th>
                            <th class="py-2.5 px-3">Engine</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Threat Name</th>
                            <th class="py-2.5 px-3">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($recentScans as $scan)
                        <tr class="hover:bg-slate-800/30">
                            <td class="py-3 px-3 text-slate-300">#{{ $scan->file_id }}</td>
                            <td class="py-3 px-3 text-slate-400">{{ $scan->scanner_engine }}</td>
                            <td class="py-3 px-3">
                                @if($scan->threat_found)
                                <span class="px-2 py-0.5 rounded text-[10px] bg-rose-500/20 text-rose-400 border border-rose-500/30">MALICIOUS</span>
                                @else
                                <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">CLEAN</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-semibold {{ $scan->threat_name ? 'text-rose-400' : 'text-slate-500' }}">
                                {{ $scan->threat_name ?: 'None' }}
                            </td>
                            <td class="py-3 px-3 text-slate-400">{{ $scan->scan_duration_ms }} ms</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Live Real-Time WebSockets / SSE Audit Log Feed -->
    <div class="glass-panel rounded-2xl p-6 border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i data-lucide="terminal" class="w-4 h-4 text-emerald-400"></i> Live Security Activity Feed (WebSockets Stream)
            </h3>
            <span class="text-xs font-mono text-slate-400">Auto-updating live events</span>
        </div>

        <div class="bg-slate-950/90 rounded-xl p-4 border border-slate-800 font-mono text-xs space-y-2 max-h-80 overflow-y-auto">
            <template x-for="event in liveEvents" :key="event.id">
                <div class="p-2.5 rounded-lg bg-slate-900/80 border border-slate-800/80 flex items-start justify-between gap-4 transition-all">
                    <div class="flex items-start space-x-3">
                        <span x-bind:class="{
                            'bg-emerald-500/20 text-emerald-400 border-emerald-500/30': event.severity === 'info',
                            'bg-amber-500/20 text-amber-400 border-amber-500/30': event.severity === 'warning',
                            'bg-rose-500/20 text-rose-400 border-rose-500/30': event.severity === 'danger' || event.severity === 'critical'
                        }" class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase" x-text="event.action"></span>

                        <div>
                            <span class="text-white font-semibold" x-text="event.user + ' (' + event.tenant + ')'"></span>
                            <span class="text-slate-400 text-[11px] block mt-0.5" x-text="JSON.stringify(event.metadata)"></span>
                        </div>
                    </div>

                    <div class="text-right text-[10px] text-slate-500 flex-shrink-0">
                        <span class="block" x-text="event.ip"></span>
                        <span class="block" x-text="event.time"></span>
                    </div>
                </div>
            </template>

            @foreach($recentSecurityEvents as $event)
            <div class="p-2.5 rounded-lg bg-slate-900/40 border border-slate-800/60 flex items-start justify-between gap-4">
                <div class="flex items-start space-x-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase
                        {{ $event->severity === 'critical' || $event->severity === 'danger' ? 'bg-rose-500/20 text-rose-400 border-rose-500/30' : ($event->severity === 'warning' ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-blue-500/20 text-blue-400 border-blue-500/30') }}">
                        {{ $event->action }}
                    </span>
                    <div>
                        <span class="text-white font-semibold">{{ $event->user ? $event->user->name : 'System' }} ({{ $event->tenant ? $event->tenant->name : 'Global' }})</span>
                        <span class="text-slate-400 text-[11px] block mt-0.5">{{ json_encode($event->metadata) }}</span>
                    </div>
                </div>
                <div class="text-right text-[10px] text-slate-500 flex-shrink-0">
                    <span class="block">{{ $event->ip_address }}</span>
                    <span class="block">{{ $event->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function liveSecurityCenter() {
        return {
            streamStatus: 'Connecting...',
            liveEvents: [],
            liveStats: {},
            init() {
                const eventSource = new EventSource('{{ route("admin.live-stream") }}');

                eventSource.onopen = () => {
                    this.streamStatus = 'Active Online';
                };

                eventSource.addEventListener('security_log', (e) => {
                    const data = JSON.parse(e.data);
                    this.liveEvents.unshift(data);
                    if (this.liveEvents.length > 20) this.liveEvents.pop();
                });

                eventSource.addEventListener('stats_update', (e) => {
                    this.liveStats = JSON.parse(e.data);
                });

                eventSource.onerror = () => {
                    this.streamStatus = 'Reconnecting...';
                };
            }
        }
    }
</script>
@endsection
