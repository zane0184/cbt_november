<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v1/auth/login')) {
            return $next($request);
        }

        $authenticatedUser = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        if (!$authenticatedUser) {
            return $next($request);
        }

        // Re-read the canonical status from the database. The authenticated
        // model may be a newly-created, not-yet-refreshed instance whose
        // database defaults are not present in its in-memory attributes.
        $activeUser = User::withoutGlobalScopes()
            ->whereKey($authenticatedUser->getKey())
            ->where('tenant_id', $authenticatedUser->tenant_id)
            ->whereNull('deleted_at')
            ->first(['id', 'is_active']);

        if ($activeUser && (bool) $activeUser->is_active) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            $token = $authenticatedUser->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }

            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_INACTIVE',
                'message' => 'Akun nonaktif. Hubungi Admin Madrasah.',
            ], 403);
        }

        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->is('login') || $request->is('logout')) {
            return $next($request);
        }

        return redirect('/login')->withErrors(['identifier' => 'Akun nonaktif. Hubungi Admin Madrasah.']);
    }
}
