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

        $apiRequest = $request->is('api/*') || $request->expectsJson();
        $isLogoutRequest = $request->is('logout') || $request->is('auth/logout')
            || $request->is('api/v1/auth/logout');
        $isPasswordChangeRequest = $request->is('account/password/change')
            || $request->is('api/v1/auth/password/change');

        if (!(bool) $user->is_active) {
            if ($apiRequest) {
                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_DISABLED',
                    'message' => 'Akun dinonaktifkan. Hubungi Admin Madrasah.',
                ], 403);
            }

            auth()->guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            return redirect('/login')->withErrors(['identifier' => 'Akun dinonaktifkan. Hubungi Admin Madrasah.']);
        }

        $authVersion = (int) ($user->auth_version ?? 1);
        if ($request->bearerToken()) {
            $accessToken = $user->currentAccessToken();
            $abilities = is_object($accessToken) && is_array($accessToken->abilities ?? null)
                ? $accessToken->abilities
                : [];
            $versionMarkers = array_values(array_filter(
                $abilities,
                fn ($ability) => is_string($ability) && str_starts_with($ability, 'account-version:')
            ));
            $expectedMarker = 'account-version:'.$authVersion;
            if (($versionMarkers !== [] && !in_array($expectedMarker, $versionMarkers, true))
                || ($versionMarkers === [] && $authVersion > 1)) {
                return response()->json([
                    'success' => false,
                    'code' => 'AUTH_SESSION_REVOKED',
                    'message' => 'Sesi autentikasi telah dicabut. Silakan login kembali.',
                ], 401);
            }
        } elseif ($request->hasSession() && auth()->check()) {
            $sessionVersion = $request->session()->get('account_auth_version');
            if (($sessionVersion === null && $authVersion > 1)
                || ($sessionVersion !== null && (int) $sessionVersion !== $authVersion)) {
                auth()->guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($apiRequest) {
                    return response()->json([
                        'success' => false,
                        'code' => 'AUTH_SESSION_REVOKED',
                        'message' => 'Sesi autentikasi telah dicabut. Silakan login kembali.',
                    ], 401);
                }

                return redirect('/login')->withErrors(['identifier' => 'Sesi telah dicabut. Silakan login kembali.']);
            }
        }

        // DatabaseSessionHandler may create the login session before the login
        // controller has an authenticated Guard ID. Bind it on the first protected
        // Web request so reset/deactivation can revoke this exact account's sessions.
        if (!$request->bearerToken()
            && $request->hasSession()
            && auth('web')->check()
            && config('session.driver') === 'database') {
            $sessionTable = (string) config('session.table', 'sessions');
            if (\\Illuminate\\Support\\Facades\\Schema::hasTable($sessionTable)
                && \\Illuminate\\Support\\Facades\\Schema::hasColumn($sessionTable, 'user_id')) {
                \\Illuminate\\Support\\Facades\\DB::table($sessionTable)
                    ->where('id', $request->session()->getId())
                    ->where(function ($query) use ($user) {
                        $query->whereNull('user_id')->orWhere('user_id', '!=', $user->getAuthIdentifier());
                    })
                    ->update(['user_id' => $user->getAuthIdentifier()]);
            }
        }

        if ((bool) $user->must_change_password && !$isPasswordChangeRequest && !$isLogoutRequest) {
            if ($apiRequest) {
                return response()->json([
                    'success' => false,
                    'code' => 'PASSWORD_CHANGE_REQUIRED',
                    'message' => 'Ganti password sementara sebelum menggunakan fitur lain.',
                ], 423);
            }

            return redirect('/account/password/change');
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
