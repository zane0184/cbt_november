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
    public function showLoginForm()
    {
        $guard = Auth::guard('web');
        if ($guard->check()) {
            $user = $guard->user();
            $roles = $user->roles()->pluck('name');
            if ($roles->count() !== 1 || $roles->first() === 'SUPER_ADMIN') {
                $guard->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            } else {
                return $this->redirectForUser($user);
            }
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
        $roles = $user->roles->pluck('name')->all();
        $allowedRoles = ['ADMIN_MADRASAH', 'KAMAD', 'GURU', 'SISWA'];
        if (count($roles) !== 1 || ! in_array($roles[0], $allowedRoles, true)) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['identifier' => 'Role pengguna tidak memiliki akses ke portal CBT Madrasah.'])->withInput($request->only('identifier'));
        }

        RateLimiter::clear($throttleKey);
        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('cbt.auth_version.' . $user->getAuthIdentifier(), (int) ($user->auth_version ?? 1));
        $identity = LocalInstallationIdentity::query()->first();
        if ($identity && $user->tenant_id) {
            TenantContext::getInstance()?->setTenantId($identity->tenant_id);
            app(TenantContext::class)->setTenantId($identity->tenant_id);
        }
        return $this->redirectForUser($user);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        TenantContext::getInstance()?->setTenantId(null); app(TenantContext::class)->setTenantId(null);
        return redirect('/login')->with('status', 'Anda telah berhasil keluar dari CBT Madrasah.');
    }

    protected function redirectForUser(User $user)
    {
        $roles = $user->roles()->pluck('name');
        if ($roles->count() !== 1) {
            return redirect('/login')->withErrors([
                'identifier' => 'Role pengguna tidak memiliki akses ke portal CBT Madrasah.',
            ]);
        }

        return match ($roles->first()) {
            'ADMIN_MADRASAH' => redirect('/admin/dashboard'),
            'KAMAD' => redirect('/'),
            'GURU' => redirect('/admin/exams'),
            'SISWA' => redirect('/student/exams'),
            default => redirect('/login')->withErrors([
                'identifier' => 'Role pengguna tidak memiliki akses ke portal CBT Madrasah.',
            ]),
        };

        return redirect('/login')->withErrors([
            'identifier' => 'Role pengguna tidak memiliki akses ke portal CBT Madrasah.',
        ]);
    }
}
