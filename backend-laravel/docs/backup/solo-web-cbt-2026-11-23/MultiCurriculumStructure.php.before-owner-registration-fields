<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MultiCurriculumStructure extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'multi_curriculum_structures';

    protected $fillable = [
        'id',
        'tenant_id',
        'multi_curriculum_version_id',
        'parent_id',
        'node_type',
        'code',
        'title',
        'sort_order',
        'status',
        'source_id', 'source_locator', 'effective_from', 'effective_until',
        'verification_status', 'verified_at', 'source_checksum', 'metadata',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumStructure::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MultiCurriculumStructure::class, 'parent_id');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(MultiLearningOutcome::class, 'multi_curriculum_structure_id');
    }

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(CurriculumReferenceSource::class, 'source_id');
    }

}
