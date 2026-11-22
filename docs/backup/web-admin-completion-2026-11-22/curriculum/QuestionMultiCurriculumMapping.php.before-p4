<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionMultiCurriculumMapping extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'question_multi_curriculum_mappings';

    protected $fillable = [
        'id',
        'tenant_id',
        'question_id',
        'multi_curriculum_version_id',
        'multi_learning_outcome_id',
        'curriculum_owner_id',
        'weight',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (QuestionMultiCurriculumMapping $model) {
            // Auto-resolve curriculum_owner_id from outcome if missing
            if (!$model->curriculum_owner_id && $model->multi_learning_outcome_id) {
                $outcome = MultiLearningOutcome::find($model->multi_learning_outcome_id);
                if ($outcome && $outcome->tenant_id) {
                    $model->curriculum_owner_id = $outcome->tenant_id;
                }
            }

            // Reject NULL curriculum_owner_id on model save
            if (!$model->curriculum_owner_id) {
                throw new \InvalidArgumentException("QuestionMultiCurriculumMapping requires a valid non-null curriculum_owner_id.");
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(MultiLearningOutcome::class, 'multi_learning_outcome_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'curriculum_owner_id');
    }
}
