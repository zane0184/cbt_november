<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSubjectAssignment extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'teacher_profile_id',
        'subject_id',
        'rombel_id',
        'academic_year_id',
        'semester_id',
        'starts_at',
        'ends_at',
        'status',
        'multi_curriculum_version_id',
        'curriculum_owner_id',
        'tenant_curriculum_registration_id',
        'curriculum_context_snapshot',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'curriculum_context_snapshot' => 'array',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActiveForDate($query, $date = null)
    {
        $date ??= now()->toDateString();

        return $query->where('status', 'ACTIVE')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', $date))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $date));
    }

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function curriculumRegistration(): BelongsTo
    {
        return $this->belongsTo(TenantCurriculumRegistration::class, 'tenant_curriculum_registration_id');
    }

}
