<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MultiCurriculum extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'multi_curriculums';

    protected $fillable = [
        'id',
        'tenant_id',
        'code',
        'name',
        'description',
        'status',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MultiCurriculumVersion::class, 'multi_curriculum_id');
    }

    public function isGlobal(): bool
    {
        return $this->tenant_id === Tenant::SYSTEM_TENANT_ID;
    }
}
