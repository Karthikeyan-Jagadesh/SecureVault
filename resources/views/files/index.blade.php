@extends('layouts.app')

@section('content')
<div x-data="{ uploadModal: false, folderModal: false, shareModal: false, activeFile: null, hashVerificationModal: false, verificationResult: null, loadingHash: false }" class="space-y-6">

    <!-- Header & Storage Usage Banner -->
    <div class="glass-panel rounded-2xl p-6 relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border-slate-800">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Secure File Storage</h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-mono bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center gap-1">
                    <i data-lucide="lock" class="w-3 h-3"></i> SHA-256 Guarded
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Multi-tenant isolated workspace with automatic ClamAV & Heuristic malware scanning</p>
        </div>

        @if(Auth::user()->tenant)
        <div class="w-full md:w-72 bg-slate-900/80 p-3.5 rounded-xl border border-slate-800">
            <div class="flex justify-between text-xs font-medium mb-1.5">
                <span class="text-slate-300">Tenant Quota Usage</span>
                <span class="font-mono text-blue-400">{{ Auth::user()->tenant->usage_percentage }}%</span>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2 rounded-full transition-all duration-500" style="width: {{ min(Auth::user()->tenant->usage_percentage, 100) }}%"></div>
            </div>
            <div class="flex justify-between text-[11px] font-mono text-slate-400 mt-1.5">
                <span>Used: {{ Auth::user()->tenant->formatted_used }}</span>
                <span>Max: {{ Auth::user()->tenant->formatted_quota }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- Action Toolbar & Breadcrumb Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Breadcrumbs -->
        <div class="flex items-center space-x-2 text-sm font-medium">
            <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white transition-colors flex items-center gap-1">
                <i data-lucide="hard-drive" class="w-4 h-4 text-blue-400"></i> Root
            </a>
            @if($currentFolder)
            <span class="text-slate-600">/</span>
            <span class="text-white font-semibold flex items-center gap-1">
                <i data-lucide="folder" class="w-4 h-4 text-amber-400"></i> {{ $currentFolder->name }}
            </span>
            @endif
        </div>

        <!-- Toolbar Buttons -->
        <div class="flex items-center space-x-3">
            <button @click="folderModal = true" class="py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition-all flex items-center space-x-2">
                <i data-lucide="folder-plus" class="w-4 h-4 text-amber-400"></i>
                <span>New Folder</span>
            </button>

            <button @click="uploadModal = true" class="py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center space-x-2">
                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                <span>Upload Secure File</span>
            </button>
        </div>
    </div>

    <!-- Directory Folders Grid -->
    @if($folders->count() > 0)
    <div>
        <h3 class="text-xs font-mono uppercase tracking-wider text-slate-400 mb-3">Folders</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($folders as $folder)
            <div class="glass-panel p-4 rounded-xl hover:border-blue-500/40 transition-all flex items-center justify-between group">
                <a href="{{ route('dashboard', ['folder_id' => $folder->id]) }}" class="flex items-center space-x-3 truncate">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center flex-shrink-0 text-amber-400 group-hover:scale-105 transition-transform">
                        <i data-lucide="folder" class="w-5 h-5"></i>
                    </div>
                    <div class="truncate">
                        <span class="block text-sm font-semibold text-white truncate">{{ $folder->name }}</span>
                        <span class="block text-[11px] text-slate-400">{{ $folder->files_count ?? 0 }} files</span>
                    </div>
                </a>

                <form method="POST" action="{{ route('folders.destroy', $folder->id) }}" onsubmit="return confirm('Delete folder and contents?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1.5 rounded-lg opacity-0 group-hover:opacity-100 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 transition-all">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Files Table -->
    <div class="glass-panel rounded-2xl overflow-hidden border-slate-800">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i data-lucide="files" class="w-4 h-4 text-blue-400"></i> Files Workspace
            </h3>
            <span class="text-xs font-mono text-slate-400">{{ $files->total() }} Total Files</span>
        </div>

        @if($files->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/90 text-slate-400 font-mono uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">File Name</th>
                        <th class="py-3.5 px-4">Size</th>
                        <th class="py-3.5 px-4">SHA-256 Hash</th>
                        <th class="py-3.5 px-4">Scan Status</th>
                        <th class="py-3.5 px-4">Uploaded By</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @foreach($files as $file)
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="py-4 px-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 flex-shrink-0">
                                    @if(in_array($file->file_extension, ['jpg', 'png', 'gif', 'svg']))
                                    <i data-lucide="image" class="w-4 h-4"></i>
                                    @elseif($file->file_extension === 'pdf')
                                    <i data-lucide="file-text" class="w-4 h-4 text-rose-400"></i>
                                    @elseif(in_array($file->file_extension, ['zip', '7z', 'gz']))
                                    <i data-lucide="archive" class="w-4 h-4 text-purple-400"></i>
                                    @else
                                    <i data-lucide="file" class="w-4 h-4"></i>
                                    @endif
                                </div>
                                <div>
                                    <span class="block text-sm font-bold text-white leading-tight hover:text-blue-400 transition-colors">{{ $file->original_name }}</span>
                                    <span class="block text-[11px] text-slate-400 font-mono">{{ uppercase($file->file_extension) }} • {{ $file->mime_type }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-4 px-4 font-mono text-slate-300">
                            {{ $file->formatted_size }}
                        </td>

                        <td class="py-4 px-4 font-mono">
                            <button @click="loadingHash = true; hashVerificationModal = true; activeFile = {{ json_encode($file) }}; fetch('{{ route('files.verify-hash', $file->id) }}').then(r => r.json()).then(data => { verificationResult = data; loadingHash = false; })"
                                    class="px-2.5 py-1 rounded bg-slate-900 hover:bg-slate-800 border border-slate-700/80 text-blue-400 hover:text-blue-300 transition-all text-[11px] flex items-center gap-1.5">
                                <i data-lucide="hash" class="w-3 h-3"></i>
                                <span>{{ substr($file->sha256_hash, 0, 10) }}...</span>
                            </button>
                        </td>

                        <td class="py-4 px-4">
                            @php $badge = $file->status_badge; @endphp
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge['class'] }} inline-flex items-center gap-1">
                                @if($file->scan_status === 'clean')
                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                @elseif($file->scan_status === 'malicious')
                                <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                @else
                                <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                                @endif
                                {{ $badge['label'] }}
                            </span>
                        </td>

                        <td class="py-4 px-4 text-slate-300 text-xs">
                            {{ $file->user ? $file->user->name : 'System' }}
                            <span class="block text-[10px] text-slate-500 font-mono">{{ $file->created_at->diffForHumans() }}</span>
                        </td>

                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                @if($file->scan_status === 'clean' && !$file->is_quarantined)
                                <a href="{{ route('files.download', $file->id) }}" title="Download File"
                                   class="p-2 rounded-lg bg-blue-600/20 hover:bg-blue-600 border border-blue-500/30 text-blue-400 hover:text-white transition-all">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                </a>
                                @else
                                <button disabled title="Infected/Quarantined File Cannot Be Downloaded"
                                        class="p-2 rounded-lg bg-slate-800 text-slate-600 cursor-not-allowed border border-slate-800">
                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                </button>
                                @endif

                                <button @click="activeFile = {{ json_encode($file) }}; shareModal = true" title="Share Permissions"
                                        class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 transition-all">
                                    <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                </button>

                                <form method="POST" action="{{ route('files.destroy', $file->id) }}" onsubmit="return confirm('Permanently delete file?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete File" class="p-2 rounded-lg bg-slate-800 hover:bg-rose-500/20 hover:text-rose-400 border border-slate-700 hover:border-rose-500/40 text-slate-400 transition-all">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-800">
            {{ $files->links() }}
        </div>
        @else
        <div class="py-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center mx-auto mb-4 text-slate-500">
                <i data-lucide="folder-open" class="w-8 h-8"></i>
            </div>
            <h4 class="text-base font-bold text-white">Workspace Empty</h4>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">No files in this directory. Upload files to run automated malware security scans.</p>
            <button @click="uploadModal = true" class="mt-4 py-2 px-4 rounded-xl bg-blue-600 text-white text-xs font-bold shadow-lg shadow-blue-500/20">
                Upload First File
            </button>
        </div>
        @endif
    </div>

    <!-- Upload File Modal -->
    <div x-show="uploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" x-transition x-cloak>
        <div class="glass-panel-glow rounded-2xl max-w-md w-full p-6 shadow-2xl border-slate-800" @click.outside="uploadModal = false">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-5 h-5 text-blue-400"></i> Secure Upload Pipeline
                </h3>
                <button @click="uploadModal = false" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>

            <form method="POST" action="{{ route('files.upload') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @if($currentFolder)
                <input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">
                @endif

                <div class="border-2 border-dashed border-slate-700/80 hover:border-blue-500/60 rounded-xl p-6 text-center bg-slate-900/60 transition-all cursor-pointer">
                    <i data-lucide="upload-cloud" class="w-10 h-10 text-blue-400 mx-auto mb-2"></i>
                    <span class="block text-xs font-semibold text-white">Select File to Upload</span>
                    <span class="block text-[11px] text-slate-400 mt-1 font-mono">Max size 50 MB • Whitelisted extensions only</span>
                    <input type="file" name="file" required class="mt-3 text-xs text-slate-300 block w-full file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500">
                </div>

                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-[11px] text-slate-300 space-y-1 font-mono">
                    <span class="block font-bold text-blue-400 uppercase tracking-wider">Automated Security Checks:</span>
                    <div class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Whitelist extension & MIME validation</div>
                    <div class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Filename sanitization & path stripping</div>
                    <div class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> ClamAV / Heuristic malware signature scan</div>
                    <div class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> SHA-256 cryptographic hash computation</div>
                </div>

                <div class="flex justify-end space-x-3 pt-2">
                    <button type="button" @click="uploadModal = false" class="py-2 px-4 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="py-2 px-5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/20">Process & Scan Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- New Folder Modal -->
    <div x-show="folderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" x-transition x-cloak>
        <div class="glass-panel rounded-2xl max-w-sm w-full p-6 border-slate-800" @click.outside="folderModal = false">
            <h3 class="text-base font-bold text-white mb-4">Create New Directory</h3>
            <form method="POST" action="{{ route('folders.store') }}" class="space-y-4">
                @csrf
                @if($currentFolder)
                <input type="hidden" name="parent_id" value="{{ $currentFolder->id }}">
                @endif
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Folder Name</label>
                    <input type="text" name="name" required placeholder="e.g. Audit Reports" class="w-full px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="folderModal = false" class="py-2 px-3 rounded-xl bg-slate-800 text-xs text-slate-300">Cancel</button>
                    <button type="submit" class="py-2 px-4 rounded-xl bg-blue-600 text-xs font-bold text-white">Create Folder</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SHA-256 Verification Modal -->
    <div x-show="hashVerificationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" x-transition x-cloak>
        <div class="glass-panel-glow rounded-2xl max-w-lg w-full p-6 border-slate-800" @click.outside="hashVerificationModal = false">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-5 h-5 text-blue-400"></i> SHA-256 Cryptographic Verification
                </h3>
                <button @click="hashVerificationModal = false" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>

            <div x-show="loadingHash" class="py-8 text-center">
                <i data-lucide="loader-2" class="w-8 h-8 text-blue-400 animate-spin mx-auto mb-2"></i>
                <p class="text-xs font-mono text-slate-400">Computing SHA-256 stream hash on server storage disk...</p>
            </div>

            <div x-show="!loadingHash && verificationResult" class="space-y-4 font-mono text-xs">
                <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase">File Name</span>
                        <span class="text-white font-bold" x-text="activeFile ? activeFile.original_name : ''"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase">Stored SHA-256 Hash</span>
                        <span class="text-blue-400 text-[11px] break-all" x-text="verificationResult ? verificationResult.stored_hash : ''"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase">Computed Disk SHA-256 Hash</span>
                        <span class="text-emerald-400 text-[11px] break-all" x-text="verificationResult ? verificationResult.current_hash : ''"></span>
                    </div>
                </div>

                <div x-show="verificationResult && verificationResult.match" class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400 flex-shrink-0"></i>
                    <span>Integrity Verified: File has not been modified or tampered with.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Share File Modal -->
    <div x-show="shareModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" x-transition x-cloak>
        <div class="glass-panel rounded-2xl max-w-md w-full p-6 border-slate-800" @click.outside="shareModal = false">
            <h3 class="text-base font-bold text-white mb-4">Share File Access</h3>
            <form x-bind:action="'/files/' + (activeFile ? activeFile.id : 0) + '/share'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Tenant User</label>
                    <select name="target_user_id" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs">
                        @foreach($tenantUsers as $tUser)
                        <option value="{{ $tUser->id }}">{{ $tUser->name }} ({{ $tUser->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Permission Level</label>
                    <select name="permission_type" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs">
                        <option value="read">Read Only</option>
                        <option value="download">Read & Download</option>
                        <option value="edit">Edit / Rename</option>
                        <option value="delete">Full Control</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="shareModal = false" class="py-2 px-3 rounded-xl bg-slate-800 text-xs text-slate-300">Cancel</button>
                    <button type="submit" class="py-2 px-4 rounded-xl bg-blue-600 text-xs font-bold text-white">Grant Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
