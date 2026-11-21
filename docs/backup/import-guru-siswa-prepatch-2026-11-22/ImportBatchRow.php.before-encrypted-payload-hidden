<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchRow extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = ['batch_id', 'sheet_name', 'row_number', 'status', 'reason', 'row_summary', 'encrypted_payload'];

    protected $casts = ['row_summary' => 'array'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }
}
