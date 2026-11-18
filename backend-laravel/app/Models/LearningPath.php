<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningPath extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'curriculum_id', 'subject_id', 'phase_id', 'code', 'name'
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(LearningPathVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(LearningPathVersion::class)->where('is_current', true);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }
}
