<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Contexts\TenantContext;
use App\Models\LocalInstallationIdentity;
use App\Services\LoginIdentityResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, LoginIdentityResolver $resolver)
    {
        $validated = $request->validate([
            'identifier' => 'required|string|max:100',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:120',
        ]);

        $identifier = trim((string) $validated['identifier']);
        $throttleKey = 'login:api:'.hash('sha256', strtolower($identifier).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan login. Silakan coba kembali nanti.',
                'retry_after' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }

        $user = $resolver->resolve($identifier);
        if (!$user || !Hash::check((string) $validated['password'], (string) $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages(['identifier' => ['Identitas atau password tidak valid.']]);
        }

        // Use the same User-row mutex as Web login and Attempt creation so a
        // second API token cannot be issued while this Siswa has an active exam.
        $loginResult = DB::transaction(function () use ($user, $validated): array {
            $lockedUser = \App\Models\User::withoutGlobalScopes()
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

                return [
                    'status' => 'OK',
                    'user' => $lockedUser,
                    'token' => $lockedUser->createToken(($validated['device_name'] ?? null) ?: 'web-cbt')->plainTextToken,
                ];
            } finally {
                $tenantContext->setTenantId($previousTenantId);
                $tenantContextInstance?->setTenantId($previousInstanceTenantId);
            }
        });

        if ($loginResult['status'] === 'ACTIVE_EXAM') {
            return response()->json([
                'success' => false,
                'message' => 'Ujian Siswa sedang aktif pada sesi/perangkat lain. Gunakan sesi Attempt yang sama.',
                'code' => 'ACTIVE_EXAM_SESSION',
            ], 409);
        }

        if ($loginResult['status'] !== 'OK') {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages(['identifier' => ['Identitas atau role pengguna tidak valid.']]);
        }

        $user = $loginResult['user'];
        RateLimiter::clear($throttleKey);

        return response()->json(['success' => true, 'message' => 'Login berhasil', 'data' => [
            'token' => $loginResult['token'],
            'user' => ['id' => $user->id, 'tenant_id' => $user->tenant_id, 'name' => $user->name, 'username' => $user->username, 'roles' => $user->roles->pluck('name')],
            'installation_id' => LocalInstallationIdentity::query()->value('installation_id'),
        ]]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if (method_exists($token, 'delete')) $token->delete();
        return response()->json(['success' => true, 'message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        $user = $request->user(); $user->load('roles.permissions');
        return response()->json(['success' => true, 'data' => [
            'id' => $user->id, 'tenant_id' => $user->tenant_id, 'name' => $user->name, 'username' => $user->username,
            'roles' => $user->roles->map(fn ($role) => ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')]),
        ]]);
    }
}
