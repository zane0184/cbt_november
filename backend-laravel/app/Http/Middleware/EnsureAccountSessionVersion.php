<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fail closed when a browser session predates the account's current auth epoch.
 * This also prevents a session from becoming valid again after a soft-deleted
 * account is restored with the same immutable user ID.
 */
class EnsureAccountSessionVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');
        $user = $guard->user();

        if (!$user) {
            return $next($request);
        }

        $sessionKey = 'cbt.auth_version.' . $user->getAuthIdentifier();
        $sessionVersion = $request->session()->get($sessionKey);
        $accountVersion = (int) ($user->auth_version ?? 1);

        // A valid remember-me recaller may establish a fresh session after the
        // previous session expired. Its remember_token is rotated on deactivate,
        // reactivate and password reset, so this path cannot revive an old cookie.
        if (!is_numeric($sessionVersion) && $guard->viaRemember()) {
            $request->session()->put($sessionKey, $accountVersion);

            return $next($request);
        }

        if (!is_numeric($sessionVersion) || (int) $sessionVersion !== $accountVersion) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
