<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumReferenceSource extends Model
{
    use HasUuids;

    protected $table = 'curriculum_reference_sources';

    protected $fillable = [
        'tenant_id', 'issuer', 'document_number', 'title', 'document_version',
        'published_at', 'effective_from', 'effective_until', 'source_uri',
        'archive_path', 'checksum_sha256', 'verification_status',
        'verified_at', 'verified_by', 'metadata',
    ];

    protected $casts = [
        'published_at' => 'date',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeVisibleToTenant(Builder $query, string $tenantId): Builder
    {
        return $query->whereIn('tenant_id', array_values(array_unique([
            $tenantId,
            Tenant::SYSTEM_TENANT_ID,
        ])));
    }

    public function curriculumVersions(): HasMany
    {
        return $this->hasMany(MultiCurriculumVersion::class, 'source_id');
    }

    public function curriculumStructures(): HasMany
    {
        return $this->hasMany(MultiCurriculumStructure::class, 'source_id');
    }

    public function learningOutcomes(): HasMany
    {
        return $this->hasMany(MultiLearningOutcome::class, 'source_id');
    }

    public function assessmentOptions(): HasMany
    {
        return $this->hasMany(AssessmentTaxonomyOption::class, 'source_id');
    }
}
