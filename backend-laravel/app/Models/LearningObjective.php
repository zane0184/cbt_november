<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningObjective extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'learning_outcome_id', 'code', 'title', 'description',
        'order_index', 'status', 'created_by', 'updated_by'
    ];

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(LearningOutcome::class, 'learning_outcome_id');
    }

    public function paths(): BelongsToMany
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_objectives', 'learning_objective_id', 'learning_path_id');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(
            Assessment::class,
            'assessment_learning_objectives',
            'learning_objective_id',
            'assessment_id'
        )->withTimestamps();
    }
}
