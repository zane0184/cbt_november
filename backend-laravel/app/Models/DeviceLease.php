<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceLease extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'exam_attempt_id',
        'registered_device_id',
        'auth_session_hash',
        'fence_generation',
        'status',
        'leased_at',
        'expires_at',
        'released_at',
        'released_by',
        'release_reason',
    ];

    protected $casts = [
        'fence_generation' => 'integer',
        'leased_at' => 'datetime',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}
