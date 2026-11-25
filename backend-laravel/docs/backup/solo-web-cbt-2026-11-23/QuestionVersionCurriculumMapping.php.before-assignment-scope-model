<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionVersionCurriculumMapping extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'question_version_curriculum_mappings';

    protected $fillable = [
        'tenant_id', 'question_version_id', 'tenant_curriculum_registration_id',
        'multi_curriculum_version_id', 'curriculum_owner_id', 'multi_learning_outcome_id', 'material_id', 'material_topic_id',
        'academic_year_id', 'semester_id', 'mapping_role', 'weight',
        'is_primary', 'created_by', 'metadata',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class, 'question_version_id');
    }

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(MultiLearningOutcome::class, 'multi_learning_outcome_id');
    }

    public function curriculumRegistration(): BelongsTo
    {
        return $this->belongsTo(TenantCurriculumRegistration::class, 'tenant_curriculum_registration_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(MaterialTopic::class, 'material_topic_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}
