<?php

namespace App\Http\Controllers;

use App\Contexts\TenantContext;
use App\Models\LocalInstallationIdentity;
use App\Models\User;
use App\Services\LoginIdentityResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthWebController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasExactlyOneOfRoles(['ADMIN_MADRASAH', 'GURU', 'SISWA'])) {
                return $this->redirectForUser($user);
            }

            // Legacy, multi-role, and retired local identities must not remain
            // authenticated or loop through a portal redirect.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            TenantContext::getInstance()?->setTenantId(null);
            app(TenantContext::class)->setTenantId(null);
        }

        return view('auth.login');
    }

    public function login(Request $request, LoginIdentityResolver $resolver)
    {
        $request->validate(['identifier' => 'required|string|max:100', 'password' => 'required|string']);
        $identifier = trim((string) $request->input('identifier'));
        $throttleKey = 'login:web:'.Str::lower($identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['identifier' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik."])->withInput($request->only('identifier'));
        }

        $user = $resolver->resolve($identifier);
        if (!$user || !Hash::check((string) $request->input('password'), (string) $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['identifier' => 'Identitas atau password tidak valid.'])->withInput($request->only('identifier'));
        }

        if (!$user->hasExactlyOneOfRoles(['ADMIN_MADRASAH', 'GURU', 'SISWA'])) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['identifier' => 'Role pengguna tidak memiliki akses ke portal CBT Madrasah.'])->withInput($request->only('identifier'));
        }

        // Serialize Web login with Attempt creation. A Siswa who already has
        // an active Attempt must resume the bound session, not mint a second one.
        $loginResult = DB::transaction(function () use ($user): array {
            $lockedUser = User::withoutGlobalScopes()
                ->where('tenant_id', $user->tenant_id)
                ->whereNull('deleted_at')
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedUser) {
                return ['status' => 'INVALID'];
            }

            $tenantContext = app(TenantContext::class);
            $previousTenantId = $tenantContext->getTenantId();
            $tenantContextInstance = TenantContext::getInstance();
            $previousInstanceTenantId = $tenantContextInstance?->getTenantId();
            $tenantContext->setTenantId((string) $lockedUser->tenant_id);
            $tenantContextInstance?->setTenantId((string) $lockedUser->tenant_id);

            try {
                $roleNames = $lockedUser->roles()->pluck('name');
                if ($roleNames->count() !== 1
                    || !in_array($roleNames->first(), ['ADMIN_MADRASAH', 'GURU', 'SISWA'], true)) {
                    return ['status' => 'INVALID'];
                }

                if ($roleNames->first() === 'SISWA'
                    && app(\App\Services\ActiveExamLoginGuard::class)->hasActiveAttempt($lockedUser)) {
                    return ['status' => 'ACTIVE_EXAM'];
                }

                return ['status' => 'OK', 'user' => $lockedUser];
            } finally {
                $tenantContext->setTenantId($previousTenantId);
                $tenantContextInstance?->setTenantId($previousInstanceTenantId);
            }
        });

        if ($loginResult['status'] === 'ACTIVE_EXAM') {
            return back()->withErrors([
                'identifier' => 'Ujian sedang aktif pada sesi/perangkat lain. Lanjutkan ujian dari sesi yang sama; hubungi Admin bila perangkat perlu dipulihkan.',
            ])->withInput($request->only('identifier'));
        }

        if ($loginResult['status'] !== 'OK') {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['identifier' => 'Identitas atau role pengguna tidak lagi valid.'])->withInput($request->only('identifier'));
        }

        $user = $loginResult['user'];
        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $identity = LocalInstallationIdentity::query()->first();
        if ($identity && $user->tenant_id) {
            TenantContext::getInstance()?->setTenantId($identity->tenant_id);
            app(TenantContext::class)->setTenantId($identity->tenant_id);
        }

        return $this->redirectForUser($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        TenantContext::getInstance()?->setTenantId(null);
        app(TenantContext::class)->setTenantId(null);

        return redirect('/login')->with('status', 'Anda telah berhasil keluar dari CBT Madrasah.');
    }

    protected function redirectForUser(User $user)
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) {
            return redirect('/admin/dashboard');
        }
        if ($user->hasExactRole('GURU')) {
            return redirect('/guru/dashboard');
        }
        if ($user->hasExactRole('SISWA')) {
            return redirect('/student/exams');
        }

        return redirect('/login')->withErrors(['identifier' => 'Role pengguna tidak memiliki akses ke portal CBT Madrasah.']);
    }
}
