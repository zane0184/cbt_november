<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestionScore extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'exam_id', 'attempt_id', 'snapshot_id', 'student_id',
        'question_type', 'awarded_score', 'max_score',
        'scoring_status', 'scoring_rule_version', 'scoring_version', 'evaluated_answer_snapshot',
        'evaluated_at', 'grader_id', 'graded_at', 'feedback'
    ];

    protected $casts = [
        'evaluated_answer_snapshot' => 'json',
        'evaluated_at' => 'datetime',
        'graded_at' => 'datetime',
        'awarded_score' => 'decimal:4',
        'max_score' => 'decimal:4',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(ExamQuestionSnapshot::class, 'snapshot_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
