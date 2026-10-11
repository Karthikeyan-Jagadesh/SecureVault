<?php

namespace App\Services;

use App\Models\FileModel;
use App\Models\QuarantineFile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuarantineService
{
    /**
     * Isolate a malicious file into the quarantine vault.
     */
    public function quarantineFile(FileModel $file, string $threatName, ?User $quarantinedBy = null): QuarantineFile
    {
        $tenant = $file->tenant;
        $originalStoragePath = $file->storage_path;

        // Generate isolated quarantine filename
        $quarantineUuid = (string) Str::uuid();
        $quarantineRelativePath = "quarantine/{$quarantineUuid}.bin";

        // Move file on disk to isolated vault
        if (Storage::exists($originalStoragePath)) {
            Storage::move($originalStoragePath, $quarantineRelativePath);
        }

        // Update file status
        $file->update([
            'scan_status' => 'quarantined',
            'is_quarantined' => true,
        ]);

        // Create Quarantine record
        $quarantineRecord = QuarantineFile::create([
            'file_id' => $file->id,
            'tenant_id' => $file->tenant_id,
            'original_filename' => $file->original_name,
            'quarantine_path' => $quarantineRelativePath,
            'threat_name' => $threatName,
            'quarantined_by' => $quarantinedBy ? $quarantinedBy->id : null,
            'resolution_status' => 'quarantined',
            'quarantined_at' => now(),
        ]);

        // Recalculate tenant storage (quarantined files do not count towards active quota)
        if ($tenant) {
            (new TenantStorageService())->reduceUsage($tenant, $file->size_bytes);
        }

        // Security Audit Log
        AuditLogService::log(
            'MALWARE_QUARANTINED',
            'security',
            'FileModel',
            $file->id,
            [
                'original_name' => $file->original_name,
                'threat_name' => $threatName,
                'quarantine_path' => $quarantineRelativePath,
                'file_hash' => $file->sha256_hash,
            ],
            'critical',
            $file->tenant_id,
            $quarantinedBy ? $quarantinedBy->id : $file->user_id
        );

        return $quarantineRecord;
    }

    /**
     * Admin action to restore a false-positive quarantined file back to tenant storage.
     */
    public function restoreFile(QuarantineFile $quarantineRecord, User $adminUser): bool
    {
        $file = $quarantineRecord->file;
        if (!$file) return false;

        $tenant = $file->tenant;

        // Restore path
        $restoredStoragePath = "secure_vault/tenants/{$file->tenant_id}/" . Str::uuid() . ".dat";

        if (Storage::exists($quarantineRecord->quarantine_path)) {
            Storage::move($quarantineRecord->quarantine_path, $restoredStoragePath);
        }

        $file->update([
            'storage_path' => $restoredStoragePath,
            'scan_status' => 'clean',
            'is_quarantined' => false,
        ]);

        $quarantineRecord->update([
            'resolution_status' => 'restored',
        ]);

        if ($tenant) {
            (new TenantStorageService())->addUsage($tenant, $file->size_bytes);
        }

        AuditLogService::log(
            'QUARANTINE_FILE_RESTORED',
            'quarantine',
            'QuarantineFile',
            $quarantineRecord->id,
            [
                'filename' => $file->original_name,
                'threat_name' => $quarantineRecord->threat_name,
                'restored_by' => $adminUser->name,
            ],
            'warning',
            $file->tenant_id,
            $adminUser->id
        );

        return true;
    }

    /**
     * Admin action to permanently purge a quarantined malicious file from disk and database.
     */
    public function purgeFile(QuarantineFile $quarantineRecord, User $adminUser): bool
    {
        if (Storage::exists($quarantineRecord->quarantine_path)) {
            Storage::delete($quarantineRecord->quarantine_path);
        }

        $file = $quarantineRecord->file;

        $quarantineRecord->update(['resolution_status' => 'deleted']);

        if ($file) {
            $file->forceDelete();
        }

        AuditLogService::log(
            'QUARANTINE_FILE_PURGED',
            'quarantine',
            'QuarantineFile',
            $quarantineRecord->id,
            [
                'filename' => $quarantineRecord->original_filename,
                'threat_name' => $quarantineRecord->threat_name,
                'purged_by' => $adminUser->name,
            ],
            'danger',
            $quarantineRecord->tenant_id,
            $adminUser->id
        );

        return true;
    }
}
