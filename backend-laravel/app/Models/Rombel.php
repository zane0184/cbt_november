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
            ->withPivot('id', 'tenant_id', 'academic_year_id')
            ->withTimestamps();

        $parentTenantId = $this->tenant_id;
        $contextTenantId = app(\App\Contexts\TenantContext::class)->getTenantId();

        if ($parentTenantId && $contextTenantId && (string) $parentTenantId !== (string) $contextTenantId) {
            $relation->whereRaw('1 = 0');
        } else {
            $tenantId = $parentTenantId ?: $contextTenantId;
            if ($tenantId) {
                $relation->wherePivot('tenant_id', $tenantId);
            } else {
                $relation->whereRaw('1 = 0');
            }
        }

        return $relation;
    }
}
