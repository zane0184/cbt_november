<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ExamAttempt extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    protected $fillable = [
        'exam_id', 'participant_id', 'attempt_number', 'status', 'device_fingerprint',
        'started_at', 'expires_at', 'submitted_at', 'server_start_reference',
        'question_order', 'options_order', 'last_activity_at'
    ];

    protected $casts = [
        'question_order' => 'array',
        'options_order' => 'array',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExamParticipant::class);
    }

    public function scoreCurrentPointer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ExamAttemptScoreCurrent::class, 'attempt_id');
    }

    public function scores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamAttemptScore::class, 'attempt_id');
    }

    /**
     * Resolve the authoritative current score only when the pointer and target
     * agree on attempt, tenant, and scoring version. Invalid pointers fail closed.
     */
    public function resolvedCurrentScore(): ?ExamAttemptScore
    {
        $pointer = $this->relationLoaded('scoreCurrentPointer')
            ? $this->getRelation('scoreCurrentPointer')
            : $this->scoreCurrentPointer()->first();

        if (!$pointer instanceof ExamAttemptScoreCurrent
            || (string) $pointer->attempt_id !== (string) $this->id
            || (string) $pointer->tenant_id !== (string) $this->tenant_id) {
            return null;
        }

        $score = $pointer->relationLoaded('currentScore')
            ? $pointer->getRelation('currentScore')
            : $pointer->currentScore()->first();

        if (!$score instanceof ExamAttemptScore
            || (string) $score->id !== (string) $pointer->current_score_id
            || (string) $score->attempt_id !== (string) $this->id
            || (string) $score->tenant_id !== (string) $this->tenant_id
            || (int) $score->scoring_version !== (int) $pointer->scoring_version) {
            return null;
        }

        return $score;
    }

    /**
     * The current score is defined only by exam_attempt_score_current.current_score_id.
     * Never infer the active score from MAX(scoring_version): historical versions
     * may remain intentionally present after a reopen/recalculation workflow.
     */
    public function score(): HasOneThrough
    {
        return $this->hasOneThrough(
            ExamAttemptScore::class,
            ExamAttemptScoreCurrent::class,
            'attempt_id',
            'id',
            'id',
            'current_score_id'
        )->whereColumn(
            'exam_attempt_scores.attempt_id',
            'exam_attempt_score_current.attempt_id'
        )->whereColumn(
            'exam_attempt_scores.tenant_id',
            'exam_attempt_score_current.tenant_id'
        )->whereColumn(
            'exam_attempt_scores.scoring_version',
            'exam_attempt_score_current.scoring_version'
        );
    }

    public function questionScores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamQuestionScore::class, 'attempt_id');
    }
}
