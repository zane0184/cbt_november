<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamProctorAssignment extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'exam_proctor_assignments';

    protected $fillable = [
        'exam_id', 'proctor_user_id', 'assigned_by', 'starts_at',
        'ends_at', 'status', 'revoked_at', 'revoked_by', 'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function proctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proctor_user_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isActiveAt(?CarbonInterface $at = null): bool
    {
        $at = $at ?? now();

        return $this->status === 'ACTIVE'
            && $this->revoked_at === null
            && $this->starts_at !== null
            && $this->starts_at->lessThanOrEqualTo($at)
            && ($this->ends_at === null || $this->ends_at->greaterThan($at));
    }

    public function scopeNotRevoked($query)
    {
        return $query->where('status', 'ACTIVE')->whereNull('revoked_at');
    }

    public function scopeWithinWindow($query, ?CarbonInterface $at = null)
    {
        $at = $at ?? now();

        return $query->where('starts_at', '<=', $at)
            ->where(function ($builder) use ($at) {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>', $at);
            });
    }
}
