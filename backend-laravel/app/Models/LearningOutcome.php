<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningOutcome extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'curriculum_id', 'phase_id', 'subject_id', 'element_id', 'code'
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(LearningOutcomeVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(LearningOutcomeVersion::class)->where('is_current', true);
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(LearningObjective::class);
    }
}
