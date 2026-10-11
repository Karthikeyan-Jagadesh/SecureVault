<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'permission_type',
    ];

    public function file()
    {
        return $this->belongsTo(FileModel::class, 'file_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
