<?php

namespace App\Services;

use App\Models\FileModel;
use Illuminate\Support\Facades\Storage;

class FileIntegrityService
{
    /**
     * Compute SHA-256 hash of a file on disk or storage.
     */
    public function computeSha256(string $filePath): string
    {
        if (Storage::exists($filePath)) {
            return hash('sha256', Storage::get($filePath));
        }
        return hash_file('sha256', $filePath);
    }

    /**
     * Verify stored SHA-256 hash matches the actual file on disk.
     */
    public function verifyIntegrity(FileModel $file): array
    {
        $currentHash = null;

        if (Storage::exists($file->storage_path)) {
            $currentHash = hash('sha256', Storage::get($file->storage_path));
        } else {
            $fullPath = Storage::path($file->storage_path);
            if (!file_exists($fullPath)) {
                return [
                    'status' => 'missing',
                    'match' => false,
                    'message' => 'File missing from server storage disk.',
                ];
            }
            $currentHash = hash_file('sha256', $fullPath);
        }

        if ($currentHash === $file->sha256_hash) {
            return [
                'status' => 'verified',
                'match' => true,
                'current_hash' => $currentHash,
                'stored_hash' => $file->sha256_hash,
                'message' => 'Cryptographic SHA-256 integrity verified successfully.',
            ];
        }

        // Audit tamper alert
        AuditLogService::log(
            'FILE_INTEGRITY_MISMATCH',
            'security',
            'FileModel',
            $file->id,
            [
                'filename' => $file->original_name,
                'expected_hash' => $file->sha256_hash,
                'computed_hash' => $currentHash,
            ],
            'critical',
            $file->tenant_id
        );

        return [
            'status' => 'tampered',
            'match' => false,
            'current_hash' => $currentHash,
            'stored_hash' => $file->sha256_hash,
            'message' => 'WARNING: File hash mismatch detected! Integrity compromised.',
        ];
    }

    /**
     * Check if a file with matching SHA-256 hash exists in the tenant.
     */
    public function findDuplicates(string $sha256Hash, int $tenantId, ?int $excludeFileId = null): array
    {
        $query = FileModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('sha256_hash', $sha256Hash)
            ->whereNull('deleted_at');

        if ($excludeFileId) {
            $query->where('id', '!=', $excludeFileId);
        }

        return $query->get()->toArray();
    }
}
