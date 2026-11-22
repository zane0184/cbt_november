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

        if (!$user) {
            return $next($request);
        }

        $eligible = (bool) $user->is_active;
        if ($eligible) {
            $roles = $user->roles()->get(['name']);
            $eligible = $roles->count() === 1
                && in_array($roles->first()?->name, ['ADMIN_MADRASAH', 'GURU', 'SISWA'], true);

            if ($eligible && $roles->first()->name === 'GURU') {
                $profile = $user->teacherProfile;
                $eligible = $profile && (
                    ($profile->employment_type === 'ASN' && preg_match('/^\\d{18}$/', (string) $profile->nip) === 1)
                    || ($profile->employment_type === 'HONORER' && preg_match('/^\\d{16}$/', (string) $profile->nik) === 1)
                );
            }

            if ($eligible && $roles->first()->name === 'SISWA') {
                $profile = $user->studentProfile;
                $eligible = $profile
                    && $profile->academic_status === 'ACTIVE'
                    && preg_match('/^\\d{10}$/', (string) $profile->nisn) === 1;
            }
        }

        if ($eligible) {
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
                'message' => 'Akun atau status akademik tidak memenuhi syarat akses. Hubungi Admin Madrasah.',
            ], 403);
        }

        return redirect()->route('login')->withErrors([
            'identifier' => 'Akun atau status akademik tidak memenuhi syarat akses. Hubungi Admin Madrasah.',
        ]);
    }
}
