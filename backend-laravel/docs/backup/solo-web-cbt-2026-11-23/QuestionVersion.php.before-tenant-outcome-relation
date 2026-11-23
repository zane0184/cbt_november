<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionVersion extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'question_id', 'major_version', 'minor_version', 'question_type',
        'stem', 'explanation', 'difficulty', 'cognitive_level',
        'estimated_time', 'default_points', 'element_id',
        'learning_outcome_id', 'learning_objective_id', 'status', 'is_current', 'created_by'
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'question_version_media')
            ->withPivot('id', 'tenant_id', 'role', 'sequence')
            ->withTimestamps();
    }

    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(LearningOutcome::class, 'learning_outcome_id');
    }

    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class, 'learning_objective_id');
    }

    public function curriculumMappings(): HasMany
    {
        return $this->hasMany(QuestionVersionCurriculumMapping::class, 'question_version_id');
    }

    public function structureMappings(): HasMany
    {
        return $this->hasMany(QuestionVersionStructureMapping::class, 'question_version_id');
    }

}
