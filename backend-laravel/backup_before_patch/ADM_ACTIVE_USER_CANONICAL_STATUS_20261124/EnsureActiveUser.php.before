<?php

namespace App\Http\Middleware;

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

        $user = $request->user() ?? Auth::guard('sanctum')->user() ?? Auth::user();
        if (!$user || (bool) $user->is_active) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            $token = $user->currentAccessToken();
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
