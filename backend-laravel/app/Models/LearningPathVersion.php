<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningPathVersion extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'learning_path_id', 'major_version', 'minor_version', 'description', 'is_current'
    ];

    public function path(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function objectives(): BelongsToMany
    {
        return $this->belongsToMany(LearningObjective::class, 'learning_path_objectives', 'learning_path_version_id', 'learning_objective_id')
            ->withPivot('order_index')
            ->orderBy('learning_path_objectives.order_index');
    }
}
