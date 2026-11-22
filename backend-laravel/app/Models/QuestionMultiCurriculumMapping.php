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
        'academic_year_id',
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
            if (!$model->curriculum_owner_id && $model->multi_learning_outcome_id) {
                $outcome = MultiLearningOutcome::withoutGlobalScopes()->whereKey($model->multi_learning_outcome_id)->first();
                if ($outcome && $outcome->tenant_id) $model->curriculum_owner_id = $outcome->tenant_id;
            }

            if (!$model->tenant_id || !$model->academic_year_id || !$model->curriculum_owner_id
                || !$model->multi_curriculum_version_id || !$model->multi_learning_outcome_id) {
                throw new \InvalidArgumentException('Question curriculum mapping requires tenant, academic year, version, outcome, and curriculum owner.');
            }

            $outcome = MultiLearningOutcome::withoutGlobalScopes()
                ->where('tenant_id', $model->curriculum_owner_id)
                ->where('multi_curriculum_version_id', $model->multi_curriculum_version_id)
                ->where('status', 'ACTIVE')->whereNull('deleted_at')
                ->whereKey($model->multi_learning_outcome_id)->first();

            if (!$outcome) {
                throw new \InvalidArgumentException('The selected outcome does not belong to the selected curriculum version and owner.');
            }

            $registration = TenantCurriculumRegistration::withoutGlobalScopes()
                ->where('tenant_id', $model->tenant_id)
                ->where('academic_year_id', $model->academic_year_id)
                ->where('multi_curriculum_version_id', $model->multi_curriculum_version_id)
                ->where('curriculum_owner_id', $model->curriculum_owner_id)
                ->where('status', 'ACTIVE')->exists();

            if (!$registration) {
                throw new \InvalidArgumentException('The curriculum version is not actively registered for this tenant and academic year.');
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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id')->withTrashed();
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
