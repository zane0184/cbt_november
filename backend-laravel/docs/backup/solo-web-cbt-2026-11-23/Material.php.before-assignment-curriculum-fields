<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'curriculum_id', 'phase_id', 'subject_id', 'grade_level_id',
        'element_id', 'learning_outcome_id', 'learning_objective_id',
        'learning_path_id', 'name', 'description', 'status', 'created_by', 'updated_by'
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(MaterialTopic::class, 'material_id');
    }

}
