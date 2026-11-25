<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCurriculumRegistration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tenant_curriculum_registrations';

    protected $fillable = [
        'id',
        'tenant_id',
        'multi_curriculum_version_id',
        'curriculum_owner_id',
        'status',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (TenantCurriculumRegistration $model) {
            $systemTenantId = Tenant::SYSTEM_TENANT_ID;

            // Enforcement of valid ownership: curriculum_owner_id MUST be System Tenant OR registrant tenant
            if ($model->curriculum_owner_id !== $systemTenantId && $model->curriculum_owner_id !== $model->tenant_id) {
                throw new \InvalidArgumentException(
                    "Invalid curriculum registration: Tenant '{$model->tenant_id}' is not authorized to register curriculum owned by '{$model->curriculum_owner_id}'."
                );
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MultiCurriculumVersion::class, 'multi_curriculum_version_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'curriculum_owner_id');
    }
}
