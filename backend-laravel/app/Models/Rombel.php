<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rombel extends Model
{
    use HasUuids, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = ['academic_year_id', 'grade_level_id', 'name', 'teacher_id', 'status'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function students(): BelongsToMany
    {
        $relation = $this->belongsToMany(StudentProfile::class, 'rombel_student', 'rombel_id', 'student_id')
            ->using(RombelStudent::class)
            ->withPivot('id', 'tenant_id')
            ->withTimestamps();

        // A persisted parent always has a tenant. Do not pass null into
        // withPivotValue(), which throws during relationship construction.
        if ($this->tenant_id) {
            $relation->wherePivot('tenant_id', $this->tenant_id);
        } else {
            $relation->whereRaw('1 = 0');
        }

        return $relation;
    }
}
