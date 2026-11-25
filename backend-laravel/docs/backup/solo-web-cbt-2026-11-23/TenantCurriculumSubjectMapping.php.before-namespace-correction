<?php

namespace App\\Models;

use App\\Traits\\TenantScoped;
use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class TenantCurriculumSubjectMapping extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'tenant_curriculum_subject_mappings';

    protected $fillable = [
        'tenant_id', 'subject_id', 'multi_curriculum_version_id',
        'multi_curriculum_structure_id', 'status', 'effective_from',
        'effective_until', 'created_by', 'metadata',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'metadata' => 'array',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function curriculumStructure(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumStructure::class, 'multi_curriculum_structure_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
