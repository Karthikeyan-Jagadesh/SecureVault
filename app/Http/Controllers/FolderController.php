<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Services\AuditLogService;
use App\Services\SecureUploadPipelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
        ]);

        $user = Auth::user();
        $sanitized = (new SecureUploadPipelineService(
            app()->make(\App\Services\TenantStorageService::class),
            app()->make(\App\Services\MalwareScannerService::class),
            app()->make(\App\Services\QuarantineService::class),
            app()->make(\App\Services\FileIntegrityService::class)
        ))->sanitizeFilename($request->input('name'));

        $folder = Folder::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'parent_id' => $request->input('parent_id'),
            'name' => $sanitized,
            'path' => '/' . $sanitized,
        ]);

        AuditLogService::log('FOLDER_CREATED', 'file', 'Folder', $folder->id, [
            'folder_name' => $sanitized,
        ], 'info', $user->tenant_id, $user->id);

        return back()->with('success', "Folder '{$sanitized}' created.");
    }

    public function destroy(Folder $folder)
    {
        $user = Auth::user();
        $name = $folder->name;

        $folder->delete();

        AuditLogService::log('FOLDER_DELETED', 'file', 'Folder', null, [
            'folder_name' => $name,
        ], 'info', $user->tenant_id, $user->id);

        return back()->with('success', "Folder '{$name}' deleted.");
    }
}
