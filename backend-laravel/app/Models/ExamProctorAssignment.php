<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamProctorAssignment extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'exam_id', 'exam_session_room_id', 'schedule_revision',
        'proctor_user_id', 'assigned_by', 'starts_at', 'expires_at',
        'status', 'assignment_reason', 'revoked_at', 'revoked_by', 'revoke_reason',
    ];

    protected $casts = [
        'schedule_revision' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function sessionRoom(): BelongsTo
    {
        return $this->belongsTo(ExamSessionRoom::class, 'exam_session_room_id');
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

    public function scopeConfirmedNow(Builder $query, $at = null): Builder
    {
        $at ??= now();

        return $query->where('status', 'CONFIRMED')
            ->whereNull('revoked_at')
            ->whereNotNull('exam_session_room_id')
            ->where('starts_at', '<=', $at)
            ->where('expires_at', '>', $at)
            ->whereHas('sessionRoom.session', function (Builder $sessionQuery): void {
                $sessionQuery->whereColumn(
                    'exam_sessions.schedule_revision',
                    'exam_proctor_assignments.schedule_revision'
                );
            });
    }
}
