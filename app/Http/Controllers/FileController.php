<?php

namespace App\Http\Controllers;

use App\Models\FileModel;
use App\Models\FilePermission;
use App\Models\Folder;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\FileIntegrityService;
use App\Services\SecureUploadPipelineService;
use App\Services\TenantStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(
        private SecureUploadPipelineService $uploadPipeline,
        private FileIntegrityService $integrityService,
        private TenantStorageService $storageService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $folderId = $request->query('folder_id');

        $currentFolder = null;
        if ($folderId) {
            $currentFolder = Folder::findOrFail($folderId);
        }

        $folders = Folder::where('parent_id', $folderId)->get();
        $files = FileModel::where('folder_id', $folderId)
            ->where('is_quarantined', false)
            ->with(['user', 'malwareScans'])
            ->latest()
            ->paginate(15);

        $tenantUsers = User::where('tenant_id', $user->tenant_id)
            ->where('id', '!=', $user->id)
            ->get();

        return view('files.index', compact('files', 'folders', 'currentFolder', 'tenantUsers'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // max 50MB
            'folder_id' => ['nullable', 'exists:folders,id'],
        ]);

        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant && !$user->isSuperAdmin()) {
            return back()->withErrors(['file' => 'User must belong to a tenant to upload files.']);
        }

        $folderId = $request->input('folder_id');
        $uploadedFile = $request->file('file');

        $result = $this->uploadPipeline->processUpload($uploadedFile, $tenant, $user, $folderId);

        if (!$result['success']) {
            if (!empty($result['is_quarantined'])) {
                return back()->with('malware_alert', $result['error']);
            }
            return back()->withErrors(['file' => $result['error']]);
        }

        $msg = "File '{$result['file']->original_name}' uploaded successfully. Passed extension, MIME, quota, and malware security scans!";
        if ($result['duplicates_count'] > 0) {
            $msg .= " (Note: {$result['duplicates_count']} duplicate file(s) with matching SHA-256 hash detected in tenant storage).";
        }

        return back()->with('success', $msg);
    }

    public function download(FileModel $file)
    {
        $user = Auth::user();

        // 1. Enforce Download Policy (Rejects Quarantined / Malicious Files!)
        if (Gate::denies('download', $file)) {
            AuditLogService::log('FILE_DOWNLOAD_BLOCKED', 'security', 'FileModel', $file->id, [
                'filename' => $file->original_name,
                'scan_status' => $file->scan_status,
                'is_quarantined' => $file->is_quarantined,
            ], 'danger', $file->tenant_id, $user->id);

            abort(403, 'SECURITY VIOLATION: Cannot download quarantined or infected file.');
        }

        // 2. File Integrity Verification prior to serving download
        $integrity = $this->integrityService->verifyIntegrity($file);
        if (!$integrity['match']) {
            abort(500, 'FILE CORRUPTION ERROR: SHA-256 hash verification failed on server disk.');
        }

        // 3. Increment Download Count
        $file->increment('downloads_count');

        AuditLogService::log('FILE_DOWNLOADED', 'file', 'FileModel', $file->id, [
            'filename' => $file->original_name,
            'sha256' => $file->sha256_hash,
            'downloads_count' => $file->downloads_count,
        ], 'info', $file->tenant_id, $user->id);

        $diskPath = Storage::path($file->storage_path);
        return response()->download($diskPath, $file->original_name);
    }

    public function verifyHash(FileModel $file)
    {
        $integrity = $this->integrityService->verifyIntegrity($file);
        return response()->json($integrity);
    }

    public function rename(Request $request, FileModel $file)
    {
        $this->authorize('delete', $file);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $sanitized = $this->uploadPipeline->sanitizeFilename($request->input('name'));
        $oldName = $file->original_name;

        $file->update(['original_name' => $sanitized]);

        AuditLogService::log('FILE_RENAMED', 'file', 'FileModel', $file->id, [
            'old_name' => $oldName,
            'new_name' => $sanitized,
        ], 'info', $file->tenant_id);

        return back()->with('success', "File renamed to '{$sanitized}'.");
    }

    public function destroy(FileModel $file)
    {
        $this->authorize('delete', $file);

        $user = Auth::user();
        $fileName = $file->original_name;
        $size = $file->size_bytes;
        $tenant = $file->tenant;

        // Delete from storage disk
        if (Storage::exists($file->storage_path)) {
            Storage::delete($file->storage_path);
        }

        $file->forceDelete();

        if ($tenant) {
            $this->storageService->reduceUsage($tenant, $size);
        }

        AuditLogService::log('FILE_DELETED', 'file', 'FileModel', null, [
            'filename' => $fileName,
            'size' => $size,
        ], 'info', $tenant->id, $user->id);

        return back()->with('success', "File '{$fileName}' permanently deleted.");
    }

    public function share(Request $request, FileModel $file)
    {
        $this->authorize('delete', $file);

        $request->validate([
            'target_user_id' => ['required', 'exists:users,id'],
            'permission_type' => ['required', 'in:read,download,edit,delete'],
        ]);

        $targetUser = User::findOrFail($request->input('target_user_id'));

        FilePermission::updateOrCreate(
            ['file_id' => $file->id, 'user_id' => $targetUser->id],
            ['permission_type' => $request->input('permission_type')]
        );

        AuditLogService::log('FILE_SHARED', 'file', 'FileModel', $file->id, [
            'filename' => $file->original_name,
            'shared_with' => $targetUser->email,
            'permission' => $request->input('permission_type'),
        ], 'info', $file->tenant_id);

        return back()->with('success', "File shared with {$targetUser->name} ({$request->input('permission_type')} permission).");
    }
}
