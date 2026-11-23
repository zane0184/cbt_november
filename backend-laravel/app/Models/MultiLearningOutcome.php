<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MultiLearningOutcome extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'multi_learning_outcomes';

    protected $fillable = [
        'id',
        'tenant_id',
        'multi_curriculum_structure_id',
        'multi_curriculum_version_id',
        'code',
        'outcome_type',
        'description',
        'status',
        'source_id', 'source_locator', 'effective_from', 'effective_until',
        'verification_status', 'verified_at', 'source_checksum', 'metadata',
        'title', 'version_code', 'sequence_number', 'is_official', 'is_editable',
        'parent_outcome_id', 'teacher_subject_assignment_id', 'academic_year_id',
        'semester_id', 'author_user_id', 'supersedes_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (MultiLearningOutcome $model) {
            // Auto-resolve version from structure if missing
            if (!$model->multi_curriculum_version_id && $model->multi_curriculum_structure_id) {
                $structure = MultiCurriculumStructure::find($model->multi_curriculum_structure_id);
                if ($structure) {
                    $model->multi_curriculum_version_id = $structure->multi_curriculum_version_id;
                }
            }

            // Enforce that valid structure-bound outcomes cannot save with NULL multi_curriculum_version_id
            if (!$model->multi_curriculum_version_id && $model->multi_curriculum_structure_id) {
                throw new \InvalidArgumentException("MultiLearningOutcome requires a valid multi_curriculum_version_id.");
            }
        });
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumStructure::class, 'multi_curriculum_structure_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'verified_at' => 'datetime',
        'metadata' => 'array',
        'sequence_number' => 'integer',
        'is_official' => 'boolean',
        'is_editable' => 'boolean',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(CurriculumReferenceSource::class, 'source_id');
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_outcome_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_outcome_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'teacher_subject_assignment_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

}
