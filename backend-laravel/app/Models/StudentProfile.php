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
        $relation = $this->belongsToMany(Rombel::class, 'rombel_student', 'student_id', 'rombel_id')
            ->using(RombelStudent::class)
            ->withPivot('id', 'tenant_id')
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
