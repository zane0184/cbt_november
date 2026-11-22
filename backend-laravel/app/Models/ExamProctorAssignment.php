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
        // Active operational lookup: deactivated Guru must not retain capability.
        return $this->belongsTo(User::class, 'proctor_user_id');
    }

    public function proctorForHistory(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proctor_user_id')->withTrashed();
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by')->withTrashed();
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by')->withTrashed();
    }

    public function scopeConfirmedNow(Builder $query, $at = null): Builder
    {
        $at ??= now();

        return $query->where('status', 'CONFIRMED')
            ->whereNull('revoked_at')
            ->whereNotNull('exam_session_room_id')
            ->where('starts_at', '<=', $at)
            ->where('expires_at', '>', $at)
            ->whereHas('sessionRoom', function (Builder $roomQuery): void {
                $roomQuery->where('exam_session_rooms.status', 'ACTIVE')
                    ->whereColumn('exam_session_rooms.tenant_id', 'exam_proctor_assignments.tenant_id')
                    ->whereColumn('exam_session_rooms.exam_id', 'exam_proctor_assignments.exam_id');
            })
            ->whereHas('sessionRoom.session', function (Builder $sessionQuery): void {
                $sessionQuery->whereIn('exam_sessions.status', ['SCHEDULED', 'ACTIVE'])
                    ->whereColumn('exam_sessions.schedule_revision', 'exam_proctor_assignments.schedule_revision')
                    ->whereColumn('exam_proctor_assignments.starts_at', '>=', 'exam_sessions.starts_at')
                    ->whereColumn('exam_proctor_assignments.expires_at', '<=', 'exam_sessions.ends_at')
                    ->whereColumn('exam_sessions.tenant_id', 'exam_proctor_assignments.tenant_id')
                    ->whereColumn('exam_sessions.exam_id', 'exam_proctor_assignments.exam_id');
            });
    }
}
