<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialTopic extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'material_topics';

    protected $fillable = [
        'tenant_id', 'material_id', 'parent_id', 'code', 'title', 'description',
        'teacher_subject_assignment_id', 'tenant_curriculum_registration_id',
        'multi_curriculum_version_id', 'curriculum_owner_id', 'tenant_learning_outcome_id',
        'version_code', 'effective_from', 'effective_until', 'status',
        'created_by', 'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
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

    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(TenantLearningOutcome::class, 'tenant_learning_outcome_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
