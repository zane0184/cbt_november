<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasUuids, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'academic_year_id', 'semester_id', 'curriculum_id',
        'subject_id', 'title', 'description',
        'assessment_type', 'status', 'weight', 'created_by',
        'assessment_function_option_id', 'assessment_scope_option_id',
        'instrument_type_option_id', 'delivery_mode_option_id',
        'activity_name', 'assessment_origin', 'external_program_name', 'assessment_taxonomy_owner_id', 'teacher_subject_assignment_id',
        'curriculum_version_id', 'curriculum_owner_id', 'tenant_curriculum_registration_id', 'curriculum_context_snapshot'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'curriculum_context_snapshot' => 'array',
    ];

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function learningObjectives(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            LearningObjective::class,
            'assessment_learning_objectives',
            'assessment_id',
            'learning_objective_id'
        )->withTimestamps();
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function assessmentFunctionOption(): BelongsTo
    {
        return $this->belongsTo(AssessmentTaxonomyOption::class, 'assessment_function_option_id');
    }

    public function assessmentScopeOption(): BelongsTo
    {
        return $this->belongsTo(AssessmentTaxonomyOption::class, 'assessment_scope_option_id');
    }

    public function instrumentTypeOption(): BelongsTo
    {
        return $this->belongsTo(AssessmentTaxonomyOption::class, 'instrument_type_option_id');
    }

    public function deliveryModeOption(): BelongsTo
    {
        return $this->belongsTo(AssessmentTaxonomyOption::class, 'delivery_mode_option_id');
    }

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'curriculum_version_id');
    }

    public function curriculumRegistration(): BelongsTo
    {
        return $this->belongsTo(TenantCurriculumRegistration::class, 'tenant_curriculum_registration_id');
    }

    public function teacherSubjectAssignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'teacher_subject_assignment_id');
    }

    public function curriculumMappings()
    {
        return $this->hasMany(AssessmentCurriculumMapping::class, 'assessment_id');
    }

    public function assessmentTaxonomyOwner(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'assessment_taxonomy_owner_id');
    }

}
