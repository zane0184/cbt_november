<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'created_by', 'type', 'template_version', 'file_sha256', 'original_filename',
        'status', 'total_rows', 'valid_rows', 'skipped_rows', 'failed_rows',
        'confirmed_at', 'completed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class, 'batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
