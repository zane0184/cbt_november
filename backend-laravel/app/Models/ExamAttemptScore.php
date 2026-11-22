<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptScore extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id', 'exam_id', 'attempt_id', 'student_id',
        'raw_score', 'max_score', 'percentage', 'normalized_score',
        'scoring_version', 'grading_status', 'calculated_at',
        'finalized_at', 'finalized_by'
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'finalized_at' => 'datetime',
        'raw_score' => 'decimal:4',
        'max_score' => 'decimal:4',
        'percentage' => 'decimal:2',
    ];

    /**
     * Restrict score history to the row explicitly selected by the authoritative
     * current-score pointer. Invalid/missing/mismatched pointers fail closed.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereExists(function (QueryBuilder $pointerQuery): void {
            $pointerQuery->selectRaw('1')
                ->from('exam_attempt_score_current as current_score_pointer')
                ->whereColumn('current_score_pointer.current_score_id', 'exam_attempt_scores.id')
                ->whereColumn('current_score_pointer.attempt_id', 'exam_attempt_scores.attempt_id')
                ->whereColumn('current_score_pointer.tenant_id', 'exam_attempt_scores.tenant_id')
                ->whereColumn('current_score_pointer.scoring_version', 'exam_attempt_scores.scoring_version');
        });
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
