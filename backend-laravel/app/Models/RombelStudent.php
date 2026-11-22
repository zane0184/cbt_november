<?php

namespace App\Models;

use App\Contexts\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;
use LogicException;

class RombelStudent extends Pivot
{
    use HasUuids;

    protected $table = 'rombel_student';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            if (!$pivot->getAttribute('id')) {
                $pivot->setAttribute('id', (string) Str::uuid());
            }

            $parentTenantId = $pivot->pivotParent?->getAttribute('tenant_id');
            $contextTenantId = app(TenantContext::class)->getTenantId();

            if ($parentTenantId && $contextTenantId && (string) $parentTenantId !== (string) $contextTenantId) {
                throw new LogicException('Rombel membership tenant context does not match its parent model.');
            }

            $tenantId = $parentTenantId ?: $contextTenantId;
            if (!$tenantId) {
                throw new LogicException('Rombel membership requires a resolved tenant.');
            }

            if ($pivot->getAttribute('tenant_id') && (string) $pivot->getAttribute('tenant_id') !== (string) $tenantId) {
                throw new LogicException('Cross-tenant rombel membership is not allowed.');
            }

            $pivot->setAttribute('tenant_id', $tenantId);

            $parentAcademicYearId = $pivot->pivotParent?->getAttribute('academic_year_id');
            $rombelId = $pivot->getAttribute('rombel_id');
            $rombel = $parentAcademicYearId
                ? null
                : Rombel::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereKey($rombelId)->first();
            $academicYearId = $parentAcademicYearId ?: $rombel?->academic_year_id;

            if (!$academicYearId) {
                throw new LogicException('Rombel membership requires a resolved academic year.');
            }

            if ($pivot->getAttribute('academic_year_id')
                && (string) $pivot->getAttribute('academic_year_id') !== (string) $academicYearId) {
                throw new LogicException('Rombel membership academic year does not match its parent rombel.');
            }

            $pivot->setAttribute('academic_year_id', $academicYearId);
        });
    }
}
