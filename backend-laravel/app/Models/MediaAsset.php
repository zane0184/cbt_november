<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'owner_type', 'owner_id', 'file_name', 'original_name', 'storage_path',
        'mime_type', 'extension', 'file_size', 'checksum', 'disk', 'metadata', 'uploaded_by'
    ];

    protected $casts = [
        'metadata' => 'json',
    ];
}
