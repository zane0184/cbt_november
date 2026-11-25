<?php

namespace App\Http\Middleware;

use App\Contexts\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(protected TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Reset context to ensure fresh state for long-lived workers
        // In unit tests where middleware is manually instantiated,
        // we must be careful not to break the reference passed in constructor.
        $instance = TenantContext::getInstance();
        if ($instance) {
            $instance->setTenantId(null);
        }
        $this->tenantContext->setTenantId(null);

        $user = $request->user() ?? $request->user('sanctum') ?? auth('sanctum')->user() ?? auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Platform Super Admin belongs exclusively to the Publisher application.
        // Reject legacy platform accounts here so existing sessions cannot reach tenant data.
        if ($user->hasRole('SUPER_ADMIN')) {
            abort(403, 'Akun Super Admin platform hanya dapat digunakan pada Publisher.');
        }

        // CBT tenant context is always derived from the authenticated user's own tenant.
        // X-Tenant-Override is intentionally ignored; cross-installation administration belongs to Publisher.
        $tenantId = $user->tenant_id;

        if ($tenantId) {
            $this->tenantContext->setTenantId($tenantId);
            if ($instance = TenantContext::getInstance()) {
                $instance->setTenantId($tenantId);
            }
        }


        return $next($request);
    }
}
