<?php

namespace App\Services;

use App\Models\FileModel;
use App\Models\Tenant;

class TenantStorageService
{
    /**
     * Check if a tenant has enough quota for an incoming upload.
     */
    public function canStoreFile(Tenant $tenant, int $incomingSizeBytes): bool
    {
        return ($tenant->storage_used_bytes + $incomingSizeBytes) <= $tenant->storage_quota_bytes;
    }

    /**
     * Increment tenant used storage bytes.
     */
    public function addUsage(Tenant $tenant, int $bytes): void
    {
        $tenant->increment('storage_used_bytes', $bytes);
    }

    /**
     * Decrement tenant used storage bytes.
     */
    public function reduceUsage(Tenant $tenant, int $bytes): void
    {
        $tenant->decrement('storage_used_bytes', max($bytes, 0));
        if ($tenant->storage_used_bytes < 0) {
            $tenant->update(['storage_used_bytes' => 0]);
        }
    }

    /**
     * Recalculate actual total storage used by all non-deleted, non-quarantined files.
     */
    public function recalculate(Tenant $tenant): void
    {
        $actualBytes = FileModel::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_quarantined', false)
            ->whereNull('deleted_at')
            ->sum('size_bytes');

        $tenant->update(['storage_used_bytes' => $actualBytes]);
    }
}
