<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MultiCurriculumVersion extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'multi_curriculum_versions';

    protected $fillable = [
        'id',
        'tenant_id',
        'multi_curriculum_id',
        'version_code',
        'title',
        'effective_year',
        'status',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculum::class, 'multi_curriculum_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function structures(): HasMany
    {
        return $this->hasMany(MultiCurriculumStructure::class, 'multi_curriculum_version_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TenantCurriculumRegistration::class, 'multi_curriculum_version_id');
    }
}
