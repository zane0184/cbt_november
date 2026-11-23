<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionVersionStructureMapping extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'question_version_structure_mappings';

    protected $fillable = [
        'tenant_id', 'question_version_id', 'tenant_curriculum_registration_id',
        'multi_curriculum_version_id', 'curriculum_owner_id', 'multi_curriculum_structure_id', 'academic_year_id', 'semester_id',
        'mapping_role', 'weight', 'created_by', 'metadata',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
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

    public function structure(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumStructure::class, 'multi_curriculum_structure_id');
    }

    public function curriculumRegistration(): BelongsTo
    {
        return $this->belongsTo(TenantCurriculumRegistration::class, 'tenant_curriculum_registration_id');
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
