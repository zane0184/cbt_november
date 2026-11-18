<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'user_id', 'nis', 'nisn', 'full_name', 'gender',
        'birth_place', 'birth_date', 'parent_name', 'phone', 'address'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rombels(): BelongsToMany
    {
        return $this->belongsToMany(Rombel::class, 'rombel_student', 'student_id', 'rombel_id')
            ->withPivot('tenant_id')
            ->withPivotValue('tenant_id', $this->tenant_id);
    }
}
