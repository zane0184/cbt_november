<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherProfile extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'user_id', 'employee_identifier', 'nip', 'nik', 'nuptk', 'full_name', 'gender', 'phone'
    ];

    public function subjectAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class, 'teacher_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
