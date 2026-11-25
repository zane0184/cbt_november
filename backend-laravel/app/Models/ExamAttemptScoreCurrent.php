<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptScoreCurrent extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'exam_attempt_score_current';

    protected $fillable = [
        'tenant_id',
        'attempt_id',
        'current_score_id',
        'scoring_version',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function currentScore(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptScore::class, 'current_score_id');
    }
}
