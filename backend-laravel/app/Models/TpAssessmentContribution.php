<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpAssessmentContribution extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'tp_achievement_id',
        'assessment_id',
        'attempt_score_id',
        'assessment_score_snapshot',
        'weight_snapshot',
        'weighted_contribution',
        'created_at',
    ];

    protected $casts = [
        'assessment_score_snapshot' => 'decimal:4',
        'weight_snapshot' => 'decimal:2',
        'weighted_contribution' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function tpAchievement(): BelongsTo
    {
        return $this->belongsTo(TpAchievement::class, 'tp_achievement_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function attemptScore(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptScore::class, 'attempt_score_id');
    }
}
