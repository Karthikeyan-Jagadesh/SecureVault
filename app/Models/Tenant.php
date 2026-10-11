<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'storage_quota_bytes',
        'storage_used_bytes',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function files()
    {
        return $this->hasMany(FileModel::class, 'tenant_id');
    }

    public function quarantineFiles()
    {
        return $this->hasMany(QuarantineFile::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasAvailableStorage(int $incomingBytes): bool
    {
        return ($this->storage_used_bytes + $incomingBytes) <= $this->storage_quota_bytes;
    }

    public function getUsagePercentageAttribute(): float
    {
        if ($this->storage_quota_bytes <= 0) return 0;
        return round(($this->storage_used_bytes / $this->storage_quota_bytes) * 100, 2);
    }

    public function getFormattedQuotaAttribute(): string
    {
        return $this->formatBytes($this->storage_quota_bytes);
    }

    public function getFormattedUsedAttribute(): string
    {
        return $this->formatBytes($this->storage_used_bytes);
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
