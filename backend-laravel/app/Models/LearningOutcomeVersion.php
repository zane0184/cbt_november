<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningOutcomeVersion extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'learning_outcome_id', 'major_version', 'minor_version', 'title', 'description', 'status', 'is_current', 'created_by'
    ];

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(LearningOutcome::class, 'learning_outcome_id');
    }
}
