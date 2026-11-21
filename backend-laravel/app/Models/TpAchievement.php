<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpAchievement extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'academic_year_id',
        'semester_id',
        'subject_id',
        'rombel_id',
        'learning_objective_id',
        'kktp_version_id',
        'kktp_threshold_snapshot',
        'score',
        'status',
        'calculation_version',
        'is_current',
        'calculated_at',
        'finalized_at',
        'finalized_by',
    ];

    protected $casts = [
        'kktp_threshold_snapshot' => 'decimal:2',
        'score' => 'decimal:2',
        'calculation_version' => 'integer',
        'is_current' => 'boolean',
        'calculated_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class, 'learning_objective_id');
    }

    public function kktpVersion(): BelongsTo
    {
        return $this->belongsTo(KktpVersion::class, 'kktp_version_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(TpAssessmentContribution::class, 'tp_achievement_id');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
