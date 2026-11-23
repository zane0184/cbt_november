<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningOutcomeStructureMapping extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'tenant_learning_outcome_structure_mappings';

    protected $fillable = [
        'tenant_id', 'tenant_learning_outcome_id', 'tenant_curriculum_registration_id',
        'multi_curriculum_version_id', 'curriculum_owner_id',
        'multi_curriculum_structure_id', 'mapping_role', 'weight',
        'created_by', 'metadata',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
        'metadata' => 'array',
    ];

    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(TenantLearningOutcome::class, 'tenant_learning_outcome_id');
    }

    public function curriculumRegistration(): BelongsTo
    {
        return $this->belongsTo(TenantCurriculumRegistration::class, 'tenant_curriculum_registration_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumStructure::class, 'multi_curriculum_structure_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
