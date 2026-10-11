<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuarantineFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'tenant_id',
        'original_filename',
        'quarantine_path',
        'threat_name',
        'quarantined_by',
        'resolution_status',
        'quarantined_at',
    ];

    protected $casts = [
        'quarantined_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function file()
    {
        return $this->belongsTo(FileModel::class, 'file_id')->withTrashed();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function quarantinedBy()
    {
        return $this->belongsTo(User::class, 'quarantined_by');
    }
}
