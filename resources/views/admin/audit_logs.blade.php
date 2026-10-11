@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ selectedMeta: null, metaModal: false }">

    <!-- Header Banner -->
    <div class="glass-panel rounded-2xl p-6 border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                <i data-lucide="file-text" class="w-6 h-6 text-blue-400"></i> Security Audit Log Explorer
            </h1>
            <p class="text-xs text-slate-400 mt-1">Immutable forensic trail of authentication, authorization, file access, and malware detection events</p>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <form method="GET" action="{{ route('admin.audit-logs') }}" class="glass-panel p-4 rounded-xl border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <label class="block text-[10px] font-mono text-slate-400 uppercase mb-1">Search Action / IP</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. LOGIN_SUCCESS or 127.0.0.1" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white">
        </div>

        <div>
            <label class="block text-[10px] font-mono text-slate-400 uppercase mb-1">Event Category</label>
            <select name="category" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white">
                <option value="">All Categories</option>
                <option value="auth" {{ request('category') === 'auth' ? 'selected' : '' }}>Authentication (auth)</option>
                <option value="file" {{ request('category') === 'file' ? 'selected' : '' }}>File Management (file)</option>
                <option value="security" {{ request('category') === 'security' ? 'selected' : '' }}>Security Violations (security)</option>
                <option value="quarantine" {{ request('category') === 'quarantine' ? 'selected' : '' }}>Quarantine Actions (quarantine)</option>
                <option value="admin" {{ request('category') === 'admin' ? 'selected' : '' }}>Admin Actions (admin)</option>
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-mono text-slate-400 uppercase mb-1">Severity Level</label>
            <select name="severity" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-white">
                <option value="">All Severities</option>
                <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
                <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
                <option value="danger" {{ request('severity') === 'danger' ? 'selected' : '' }}>Danger</option>
                <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
            </select>
        </div>

        <div class="flex items-end space-x-2">
            <button type="submit" class="w-full py-2 px-4 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold transition-all">Filter Logs</button>
            <a href="{{ route('admin.audit-logs') }}" class="py-2 px-3 rounded-lg bg-slate-800 text-slate-300 font-semibold hover:bg-slate-700">Reset</a>
        </div>
    </form>

    <!-- Logs Table -->
    <div class="glass-panel rounded-2xl overflow-hidden border-slate-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-slate-900/90 text-slate-400 uppercase border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Tenant</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4">Severity</th>
                        <th class="py-3 px-4 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($logs as $log)
                    <tr class="hover:bg-slate-800/30">
                        <td class="py-3 px-4 text-slate-400">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>

                        <td class="py-3 px-4 font-bold text-white">
                            {{ $log->action }}
                        </td>

                        <td class="py-3 px-4 text-slate-400">
                            {{ $log->event_category }}
                        </td>

                        <td class="py-3 px-4 text-slate-300">
                            {{ $log->user ? $log->user->name : 'System/Guest' }}
                        </td>

                        <td class="py-3 px-4 text-slate-400">
                            {{ $log->tenant ? $log->tenant->name : 'Global' }}
                        </td>

                        <td class="py-3 px-4 text-blue-400">
                            {{ $log->ip_address }}
                        </td>

                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase
                                {{ $log->severity === 'critical' || $log->severity === 'danger' ? 'bg-rose-500/20 text-rose-400 border-rose-500/30' : ($log->severity === 'warning' ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-blue-500/20 text-blue-400 border-blue-500/30') }}">
                                {{ $log->severity }}
                            </span>
                        </td>

                        <td class="py-3 px-4 text-right">
                            <button @click="selectedMeta = {{ json_encode($log->metadata) }}; metaModal = true"
                                    class="p-1.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] transition-all">
                                <i data-lucide="eye" class="w-3.5 h-3.5 inline-block mr-1"></i> JSON
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-800">
            {{ $logs->links() }}
        </div>
    </div>

    <!-- Metadata Modal -->
    <div x-show="metaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" x-transition x-cloak>
        <div class="glass-panel rounded-2xl max-w-md w-full p-6 border-slate-800" @click.outside="metaModal = false">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Event Metadata Payload</h3>
                <button @click="metaModal = false" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <pre class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-emerald-400 font-mono text-xs overflow-x-auto" x-text="JSON.stringify(selectedMeta, null, 2)"></pre>
        </div>
    </div>
</div>
@endsection
