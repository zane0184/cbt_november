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
        'tenant_id', 'exam_id', 'exam_room_id', 'exam_session_room_id', 'schedule_revision',
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

    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class, 'exam_room_id');
    }

    /** Legacy history relation; direct operational assignments use room(). */
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
            ->whereNotNull('exam_room_id')
            ->where('starts_at', '<=', $at)
            ->where('expires_at', '>', $at)
            ->whereExists(function ($allocation) {
                $allocation->selectRaw('1')
                    ->from('exam_room_exam as direct_exam_rooms')
                    ->whereColumn('direct_exam_rooms.tenant_id', 'exam_proctor_assignments.tenant_id')
                    ->whereColumn('direct_exam_rooms.exam_id', 'exam_proctor_assignments.exam_id')
                    ->whereColumn('direct_exam_rooms.exam_room_id', 'exam_proctor_assignments.exam_room_id')
                    ->where('direct_exam_rooms.status', 'ACTIVE');
            })
            ->whereHas('exam', function (Builder $examQuery): void {
                $examQuery->where('exams.proctoring_mode', 'CENTRALIZED')
                    ->where('exams.status', 'PUBLISHED')
                    ->whereNotNull('exams.locked_at')
                    ->whereColumn('exams.tenant_id', 'exam_proctor_assignments.tenant_id');
            });
    }
