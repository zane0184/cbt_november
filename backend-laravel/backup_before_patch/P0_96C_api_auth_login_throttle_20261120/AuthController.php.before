<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LocalInstallationIdentity;
use App\Services\LoginIdentityResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, LoginIdentityResolver $resolver)
    {
        $request->validate(['identifier' => 'required|string|max:100', 'password' => 'required|string', 'device_name' => 'nullable|string|max:120']);
        $user = $resolver->resolve((string) $request->input('identifier'));
        if (! $user || ! Hash::check((string) $request->input('password'), (string) $user->password) || $user->hasRole('SUPER_ADMIN')) {
            throw ValidationException::withMessages(['identifier' => ['Identitas atau password tidak valid.']]);
        }
        $token = $user->createToken($request->input('device_name') ?: 'android-cbt')->plainTextToken;
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
