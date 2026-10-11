<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'files';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'folder_id',
        'original_name',
        'storage_path',
        'mime_type',
        'file_extension',
        'size_bytes',
        'sha256_hash',
        'scan_status',
        'is_quarantined',
        'downloads_count',
    ];

    protected $casts = [
        'is_quarantined' => 'boolean',
        'downloads_count' => 'integer',
        'size_bytes' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function malwareScans()
    {
        return $this->hasMany(MalwareScan::class, 'file_id');
    }

    public function quarantineRecord()
    {
        return $this->hasOne(QuarantineFile::class, 'file_id');
    }

    public function permissions()
    {
        return $this->hasMany(FilePermission::class, 'file_id');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->scan_status) {
            'clean' => ['label' => 'Clean & Verified', 'class' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'],
            'pending' => ['label' => 'Scanning...', 'class' => 'bg-amber-500/20 text-amber-400 border-amber-500/30 animate-pulse'],
            'malicious' => ['label' => 'Malware Detected', 'class' => 'bg-rose-500/20 text-rose-400 border-rose-500/30'],
            'quarantined' => ['label' => 'Quarantined', 'class' => 'bg-purple-500/20 text-purple-400 border-purple-500/30'],
            default => ['label' => 'Scan Error', 'class' => 'bg-gray-500/20 text-gray-400 border-gray-500/30'],
        };
    }
}
