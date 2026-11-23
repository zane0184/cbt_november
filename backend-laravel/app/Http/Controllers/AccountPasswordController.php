<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AccountPasswordController extends Controller
{
    public function show(Request $request)
    {
        abort_unless((bool) $request->user()->must_change_password, 404);

        return view('auth.force-password-change');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:14', 'confirmed', 'different:current_password'],
        ]);

        $userId = (string) $request->user()->id;
        $tenantId = (string) $request->user()->tenant_id;

        DB::transaction(function () use ($validated, $userId, $tenantId): void {
            $lockedUser = User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!(bool) $lockedUser->is_active) {
                abort(403, 'Akun dinonaktifkan.');
            }

            if (!(bool) $lockedUser->must_change_password) {
                abort(409, 'Akun tidak sedang diwajibkan mengganti password.');
            }

            if (!Hash::check((string) $validated['current_password'], (string) $lockedUser->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password sementara tidak sesuai.'],
                ]);
            }

            $lockedUser->password = Hash::make((string) $validated['password']);
            $lockedUser->must_change_password = false;
            $lockedUser->auth_version = (int) $lockedUser->auth_version + 1;
            $lockedUser->save();

            // Revoke every Sanctum credential for this one target account.
            $lockedUser->tokens()->delete();

            // Database sessions are revoked directly when that driver is used.
            // Other session drivers are invalidated by auth_version on next request.
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $lockedUser->id)->delete();
            }

            app(AuditService::class)->log(
                'FORCE_CHANGE_PASSWORD',
                'User',
                (string) $lockedUser->id,
                null,
                ['outcome' => 'SUCCESS', 'auth_version' => (int) $lockedUser->auth_version]
            );
        }, 3);

        if ($request->is('api/*')) {
            return response()->json([
                'success' => true,
                'code' => 'PASSWORD_CHANGED_REAUTH_REQUIRED',
                'message' => 'Password berhasil diganti. Login kembali menggunakan password baru.',
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'Password berhasil diganti. Silakan login kembali.');
    }
}
