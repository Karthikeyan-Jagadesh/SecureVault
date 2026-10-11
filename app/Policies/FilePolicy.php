<?php

namespace App\Policies;

use App\Models\FileModel;
use App\Models\User;

class FilePolicy
{
    /**
     * Determine whether the user can view the file details.
     */
    public function view(User $user, FileModel $file): bool
    {
        if ($user->isSuperAdmin()) return true;

        if ($user->tenant_id !== $file->tenant_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can download the file.
     * CRITICAL SECURITY REQUIREMENT: Infected or Quarantined files CANNOT be downloaded by normal users!
     */
    public function download(User $user, FileModel $file): bool
    {
        if ($user->isSuperAdmin()) return true;

        // Strict Quarantine & Malware check
        if ($file->is_quarantined || $file->scan_status !== 'clean') {
            return false;
        }

        if ($user->tenant_id !== $file->tenant_id) {
            return false;
        }

        // Check if user is owner, tenant admin, or has explicit file permission
        if ($file->user_id === $user->id || $user->isTenantAdmin()) {
            return true;
        }

        return $file->permissions->contains(function ($perm) use ($user) {
            return $perm->user_id === $user->id && in_array($perm->permission_type, ['read', 'download', 'edit', 'delete']);
        });
    }

    /**
     * Determine whether the user can delete the file.
     */
    public function delete(User $user, FileModel $file): bool
    {
        if ($user->isSuperAdmin()) return true;

        if ($user->tenant_id !== $file->tenant_id) {
            return false;
        }

        return $file->user_id === $user->id || $user->isTenantAdmin();
    }
}
