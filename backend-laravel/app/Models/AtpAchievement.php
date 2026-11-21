<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtpAchievement extends Model
{
    use HasUuids, HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'academic_year_id',
        'semester_id',
        'subject_id',
        'rombel_id',
        'learning_path_id',
        'learning_path_version_id',
        'score',
        'status',
        'calculation_version',
        'is_current',
        'calculated_at',
        'finalized_at',
        'finalized_by',
    ];

    protected $casts = [
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

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function learningPathVersion(): BelongsTo
    {
        return $this->belongsTo(LearningPathVersion::class, 'learning_path_version_id');
    }

    public function tpSources(): HasMany
    {
        return $this->hasMany(AtpTpSource::class, 'atp_achievement_id');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
