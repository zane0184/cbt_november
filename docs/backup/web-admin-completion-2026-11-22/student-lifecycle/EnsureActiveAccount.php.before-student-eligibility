<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (bool) $user->is_active) {
            return $next($request);
        }

        $token = $user->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        if (Auth::guard('web')->check()
            && (string) Auth::guard('web')->id() === (string) $user->id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif. Hubungi Admin Madrasah.',
            ], 403);
        }

        return redirect()->route('login')->withErrors([
            'identifier' => 'Akun tidak aktif. Hubungi Admin Madrasah.',
        ]);
    }
}
