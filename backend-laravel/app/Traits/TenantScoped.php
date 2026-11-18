<?php

namespace App\Traits;

use App\Contexts\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait TenantScoped
{
    protected static function getTenantIdFromContext(): ?string
    {
        if (app()->bound(TenantContext::class)) {
            $id = app(TenantContext::class)->getTenantId();
            if ($id) {
                return $id;
            }
        }

        if ($instance = TenantContext::getInstance()) {
            $id = $instance->getTenantId();
            if ($id) {
                return $id;
            }
        }

        return auth()->check() ? auth()->user()->tenant_id : null;
    }

    public static function bootTenantScoped(): void
    {
        static::creating(function ($model) {
            $tenantId = static::getTenantIdFromContext();

            if (!$tenantId) {
                throw new \RuntimeException("Attempted to create a tenant-scoped model [" . get_class($model) . "] without a valid tenant context.");
            }

            // Force overwrite any tenant_id provided by the client
            $model->tenant_id = $tenantId;
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::getTenantIdFromContext();

            if ($tenantId) {
                $builder->where($builder->getQuery()->from . '.tenant_id', $tenantId);
            } else {
                // FAIL-CLOSED: Force a condition that returns no results if context is missing
                $builder->whereRaw('1 = 0');
            }
        });
    }
}
