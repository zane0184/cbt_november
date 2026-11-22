<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
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
        return $this->belongsTo(User::class, 'student_id')->withTrashed();
    }
}
