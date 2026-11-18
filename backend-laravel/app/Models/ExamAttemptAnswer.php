<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptAnswer extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'exam_id', 'exam_attempt_id', 'exam_question_snapshot_id',
        'student_id', 'answer_payload', 'answer_state',
        'revision_number', 'client_request_id', 'answered_at', 'saved_at'
    ];

    protected $casts = [
        'answer_payload' => 'array',
        'answered_at' => 'datetime',
        'saved_at' => 'datetime',
        'revision_number' => 'integer',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(ExamQuestionSnapshot::class, 'exam_question_snapshot_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
