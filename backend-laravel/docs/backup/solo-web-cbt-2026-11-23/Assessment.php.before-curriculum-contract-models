<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasUuids, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'academic_year_id', 'semester_id', 'curriculum_id',
        'subject_id', 'title', 'description',
        'assessment_type', 'status', 'weight', 'created_by'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function learningObjectives(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            LearningObjective::class,
            'assessment_learning_objectives',
            'assessment_id',
            'learning_objective_id'
        )->withTimestamps();
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
