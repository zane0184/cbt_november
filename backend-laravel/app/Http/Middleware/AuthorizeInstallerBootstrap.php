<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeInstallerBootstrap
{
    private const SESSION_KEY = 'installer.bootstrap_authorized_at';
    private const SESSION_TTL_SECONDS = 3600;

    public function handle(Request $request, Closure $next): Response
    {
        // The authorization page and its POST are the only public installer endpoints.
        if ($request->routeIs('installer.authorize.form', 'installer.authorize')) {
            return $next($request);
        }

        $authorizedAt = $request->session()->get(self::SESSION_KEY);
        $isFresh = is_int($authorizedAt)
            && $authorizedAt <= now()->timestamp
            && (now()->timestamp - $authorizedAt) <= self::SESSION_TTL_SECONDS;

        if (!$isFresh) {
            $request->session()->forget(self::SESSION_KEY);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Otorisasi operator diperlukan sebelum menggunakan installer.',
                ], 403);
            }

            return redirect()->route('installer.authorize.form')
                ->with('error', 'Masukkan token otorisasi operator untuk membuka installer.');
        }

        return $next($request);
    }
}
