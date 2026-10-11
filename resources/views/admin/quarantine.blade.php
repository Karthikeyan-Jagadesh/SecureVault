@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="glass-panel-glow rounded-2xl p-6 border-purple-500/30 bg-purple-950/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Quarantine Vault Console</h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-mono bg-purple-500/20 text-purple-300 border border-purple-500/40 font-bold">
                    ISOLATED STORAGE VAULT
                </span>
            </div>
            <p class="text-xs text-slate-300 mt-1">Review, restore false positives, or permanently purge malicious files isolated by the malware scanner</p>
        </div>

        <div class="px-4 py-2 rounded-xl bg-purple-900/40 border border-purple-500/30 text-xs font-mono text-purple-200">
            <i data-lucide="lock" class="w-4 h-4 inline-block mr-1"></i> Access Restricted to Administrators
        </div>
    </div>

    <!-- Quarantined Files Table -->
    <div class="glass-panel rounded-2xl overflow-hidden border-slate-800">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-4 h-4 text-purple-400"></i> Isolated Threats Registry
            </h3>
            <span class="text-xs font-mono text-purple-400">{{ $quarantinedFiles->total() }} Quarantined Items</span>
        </div>

        @if($quarantinedFiles->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/90 text-slate-400 font-mono uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">File Name</th>
                        <th class="py-3.5 px-4">Threat Name</th>
                        <th class="py-3.5 px-4">Tenant</th>
                        <th class="py-3.5 px-4">Isolation Path</th>
                        <th class="py-3.5 px-4">Quarantined At</th>
                        <th class="py-3.5 px-4">Resolution</th>
                        <th class="py-3.5 px-6 text-right">Admin Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @foreach($quarantinedFiles as $item)
                    <tr class="hover:bg-slate-800/30">
                        <td class="py-4 px-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-lg bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400">
                                    <i data-lucide="bug" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <span class="block font-bold text-white">{{ $item->original_filename }}</span>
                                    <span class="block text-[10px] text-slate-500 font-mono">Quarantine ID: #{{ $item->id }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-4 px-4 font-mono font-bold text-rose-400">
                            {{ $item->threat_name }}
                        </td>

                        <td class="py-4 px-4 text-slate-300">
                            {{ $item->tenant ? $item->tenant->name : 'N/A' }}
                        </td>

                        <td class="py-4 px-4 font-mono text-[11px] text-slate-400">
                            {{ $item->quarantine_path }}
                        </td>

                        <td class="py-4 px-4 text-slate-400 text-xs">
                            {{ $item->quarantined_at->diffForHumans() }}
                        </td>

                        <td class="py-4 px-4">
                            @if($item->resolution_status === 'quarantined')
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/40">ISOLATED</span>
                            @elseif($item->resolution_status === 'restored')
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">RESTORED</span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40">PURGED</span>
                            @endif
                        </td>

                        <td class="py-4 px-6 text-right">
                            @if($item->resolution_status === 'quarantined')
                            <div class="flex items-center justify-end space-x-2">
                                <form method="POST" action="{{ route('admin.quarantine.restore', $item->id) }}" onsubmit="return confirm('Restore false positive file to clean storage?')">
                                    @csrf
                                    <button type="submit" class="py-1.5 px-3 rounded-lg bg-emerald-600/20 hover:bg-emerald-600 border border-emerald-500/30 text-emerald-400 hover:text-white transition-all text-xs font-bold flex items-center gap-1">
                                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Restore
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.quarantine.purge', $item->id) }}" onsubmit="return confirm('PERMANENTLY PURGE malicious file from server?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="py-1.5 px-3 rounded-lg bg-rose-600/20 hover:bg-rose-600 border border-rose-500/30 text-rose-400 hover:text-white transition-all text-xs font-bold flex items-center gap-1">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Purge File
                                    </button>
                                </form>
                            </div>
                            @else
                            <span class="text-slate-500 text-xs italic">Resolved</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-800">
            {{ $quarantinedFiles->links() }}
        </div>
        @else
        <div class="py-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center mx-auto mb-4 text-emerald-400">
                <i data-lucide="shield-check" class="w-8 h-8"></i>
            </div>
            <h4 class="text-base font-bold text-white">Quarantine Vault Clean</h4>
            <p class="text-xs text-slate-400 mt-1">No malicious or quarantined files currently require administrator review.</p>
        </div>
        @endif
    </div>
</div>
@endsection
