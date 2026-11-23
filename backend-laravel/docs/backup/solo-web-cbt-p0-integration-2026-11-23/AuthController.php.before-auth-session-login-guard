<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LocalInstallationIdentity;
use App\Services\LoginIdentityResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        if (! $user || ! Hash::check((string) $validated['password'], (string) $user->password) || $user->hasRole('SUPER_ADMIN')) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages(['identifier' => ['Identitas atau password tidak valid.']]);
        }

        RateLimiter::clear($throttleKey);
        $token = $user->createToken(($validated['device_name'] ?? null) ?: 'android-cbt')->plainTextToken;

        return response()->json(['success' => true, 'message' => 'Login berhasil', 'data' => [
            'token' => $token,
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
