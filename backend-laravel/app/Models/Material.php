<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'curriculum_id', 'phase_id', 'subject_id', 'grade_level_id',
        'element_id', 'learning_outcome_id', 'learning_objective_id',
        'learning_path_id', 'name', 'description', 'status', 'created_by', 'updated_by',
        'material_code', 'version_code', 'teacher_subject_assignment_id',
        'tenant_curriculum_registration_id', 'multi_curriculum_version_id', 'curriculum_owner_id',
        'academic_year_id', 'semester_id', 'multi_curriculum_structure_id',
        'tenant_learning_outcome_id', 'effective_from', 'effective_until',
        'is_current', 'supersedes_id', 'metadata'
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_current' => 'boolean',
        'metadata' => 'array',
    ];

    public function teacherSubjectAssignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'teacher_subject_assignment_id');
    }

    public function curriculumRegistration(): BelongsTo
    {
        return $this->belongsTo(TenantCurriculumRegistration::class, 'tenant_curriculum_registration_id');
    }

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function curriculumStructure(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumStructure::class, 'multi_curriculum_structure_id');
    }

    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(TenantLearningOutcome::class, 'tenant_learning_outcome_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(MaterialTopic::class, 'material_id');
    }

}
