<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ImportActivationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

class ImportAccountActivationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        abort_unless($request->hasValidSignature(), 403, 'Tautan aktivasi tidak valid atau kedaluwarsa.');
        $record = ImportActivationToken::withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')->where('expires_at', '>', now())->first();
        abort_unless($record, 410, 'Tautan aktivasi sudah digunakan atau kedaluwarsa.');

        return response()->view('auth.activate-import-account', [
            'token' => $token,
            'action' => $request->fullUrl(),
        ])->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, string $token): Response
    {
        abort_unless($request->hasValidSignature(), 403, 'Tautan aktivasi tidak valid atau kedaluwarsa.');
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(14)->mixedCase()->numbers()->symbols()],
        ]);

        DB::transaction(function () use ($token, $validated, $request): void {
            $record = ImportActivationToken::withoutGlobalScopes()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()->first();
            abort_unless($record && !$record->used_at && $record->expires_at->isFuture(), 410, 'Tautan aktivasi sudah digunakan atau kedaluwarsa.');

            $user = \App\Models\User::withoutGlobalScopes()->where('tenant_id', $record->tenant_id)
                ->whereKey($record->user_id)->lockForUpdate()->firstOrFail();
            abort_unless($user->roles()->whereIn('name', ['GURU', 'SISWA'])->count() === 1, 403);
            $hasProfile = \App\Models\TeacherProfile::withoutGlobalScopes()->where('tenant_id', $record->tenant_id)->where('user_id', $user->id)->exists()
                || \App\Models\StudentProfile::withoutGlobalScopes()->where('tenant_id', $record->tenant_id)->where('user_id', $user->id)->exists();
            abort_unless($hasProfile, 403);

            $user->forceFill(['password' => Hash::make($validated['password'])])->save();
            $record->update(['used_at' => now()]);
            ImportActivationToken::withoutGlobalScopes()->where('tenant_id', $record->tenant_id)
                ->where('user_id', $user->id)->whereNull('used_at')->where('id', '<>', $record->id)
                ->update(['used_at' => now()]);
            AuditLog::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $record->tenant_id,
                'user_id' => $user->id,
                'action' => 'IMPORT_ACCOUNT_ACTIVATED',
                'entity_type' => 'User',
                'entity_id' => $user->id,
                'old_values' => null,
                'new_values' => ['activation' => 'one_time_link'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }, 3);

        return response()->view('auth.import-account-activated');
    }
}
