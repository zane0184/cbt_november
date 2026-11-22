<?php

namespace App\Http\Controllers;

use App\Contexts\TenantContext;
use App\Models\LocalInstallationIdentity;
use App\Models\User;
use App\Services\LoginIdentityResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthWebController extends Controller
{
    private const PORTAL_ROLES = ['ADMIN_MADRASAH', 'GURU', 'SISWA'];

    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectForUser(Auth::user());
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
        if (! $user || ! Hash::check((string) $request->input('password'), (string) $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['identifier' => 'Identitas atau password tidak valid.'])->withInput($request->only('identifier'));
        }

        $role = $this->exactPortalRole($user);
        if ($role === null) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['identifier' => 'Role pengguna tidak memiliki akses ke portal CBT Madrasah.'])->withInput($request->only('identifier'));
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $identity = LocalInstallationIdentity::query()->first();
        if ($identity && $user->tenant_id) {
            TenantContext::getInstance()?->setTenantId($identity->tenant_id);
            app(TenantContext::class)->setTenantId($identity->tenant_id);
        }

        return $this->redirectForUser($user, $role);
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

    private function exactPortalRole(User $user): ?string
    {
        // Always read the persisted role set; do not trust a cached Eloquent relation.
        $roles = $user->roles()->pluck('name')->all();

        if (count($roles) !== 1 || ! in_array($roles[0], self::PORTAL_ROLES, true)) {
            return null;
        }

        return $roles[0];
    }

    protected function redirectForUser(User $user, ?string $role = null)
    {
        $role ??= $this->exactPortalRole($user);

        return match ($role) {
            'ADMIN_MADRASAH' => redirect('/admin/dashboard'),
            'GURU' => redirect('/guru/dashboard'),
            'SISWA' => redirect('/student/exams'),
            default => $this->denyPortalSession(),
        };
    }

    private function denyPortalSession()
    {
        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        TenantContext::getInstance()?->setTenantId(null);
        app(TenantContext::class)->setTenantId(null);

        return redirect('/login')->withErrors([
            'identifier' => 'Role pengguna tidak memiliki akses ke portal CBT Madrasah.',
        ]);
    }
}
