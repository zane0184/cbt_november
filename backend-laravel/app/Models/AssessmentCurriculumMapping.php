<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentCurriculumMapping extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'assessment_id',
        'teacher_subject_assignment_id',
        'tenant_curriculum_registration_id',
        'multi_curriculum_version_id',
        'curriculum_owner_id',
        'multi_learning_outcome_id',
        'tenant_learning_outcome_id',
        'material_id',
        'material_topic_id',
        'academic_year_id',
        'semester_id',
        'mapping_role',
        'weight',
        'is_primary',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'weight' => 'decimal:4',
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'teacher_subject_assignment_id');
    }

    public function curriculumRegistration(): BelongsTo
    {
        return $this->belongsTo(TenantCurriculumRegistration::class, 'tenant_curriculum_registration_id');
    }

    public function curriculumOutcome(): BelongsTo
    {
        return $this->belongsTo(MultiLearningOutcome::class, 'multi_learning_outcome_id');
    }

    public function tenantOutcome(): BelongsTo
    {
        return $this->belongsTo(TenantLearningOutcome::class, 'tenant_learning_outcome_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function materialTopic(): BelongsTo
    {
        return $this->belongsTo(MaterialTopic::class, 'material_topic_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
