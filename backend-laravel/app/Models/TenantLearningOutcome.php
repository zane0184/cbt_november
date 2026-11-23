<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantLearningOutcome extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'tenant_learning_outcomes';

    protected $fillable = [
        'tenant_id', 'tenant_curriculum_registration_id', 'multi_curriculum_version_id',
        'curriculum_owner_id', 'subject_id', 'multi_curriculum_structure_id',
        'teacher_subject_assignment_id', 'academic_year_id', 'semester_id',
        'parent_cp_id', 'parent_tp_id', 'outcome_type', 'code', 'title',
        'description', 'sequence_number', 'version_code', 'supersedes_id',
        'is_current', 'status', 'author_user_id', 'metadata',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'is_current' => 'boolean',
        'metadata' => 'array',
    ];

    public function parentCp(): BelongsTo
    {
        return $this->belongsTo(MultiLearningOutcome::class, 'parent_cp_id');
    }

    public function parentTp(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_tp_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_tp_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'teacher_subject_assignment_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function curriculumRegistration(): BelongsTo
    {
        return $this->belongsTo(TenantCurriculumRegistration::class, 'tenant_curriculum_registration_id');
    }

    public function curriculumStructure(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumStructure::class, 'multi_curriculum_structure_id');
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

    public function structureMappings(): HasMany
    {
        return $this->hasMany(LearningOutcomeStructureMapping::class, 'tenant_learning_outcome_id');
    }

    public function questionVersionMappings(): HasMany
    {
        return $this->hasMany(QuestionVersionTenantOutcomeMapping::class, 'tenant_learning_outcome_id');
    }
}
