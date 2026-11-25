<?php

namespace App\Http\Controllers;

use App\Models\LocalInstallationIdentity;
use App\Models\LocalLicenseState;
use App\Services\InstallerStateStore;
use App\Services\Licensing\PublisherLicenseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class LicenseStatusController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user() && $request->user()->roles()->whereIn('name', ['ADMIN_MADRASAH', 'OPERATOR'])->exists(), 403);

        $installState = [];
        try {
            $candidate = app(InstallerStateStore::class)->read();
            if ((string) ($candidate['tenant_id'] ?? '') === (string) $request->user()?->tenant_id) {
                $installState = $candidate;
            }
        } catch (Throwable) {
            // Status remains available even if the local installer checkpoint is damaged.
        }

        $tenantId = (string) ($request->user()?->tenant_id ?? '');
        $identity = $tenantId !== ''
            ? LocalInstallationIdentity::withoutGlobalScopes()->where('tenant_id', $tenantId)->first()
            : null;
        $state = $identity
            ? LocalLicenseState::withoutGlobalScopes()->where('installation_identity_id', $identity->id)->first()
            : null;

        return view('license.status', [
            'identity' => $identity,
            'state' => $state,
            'installState' => $installState,
        ]);
    }

    public function refresh(Request $request, PublisherLicenseClient $client)
    {
        abort_unless($request->user() && $request->user()->roles()->whereIn('name', ['ADMIN_MADRASAH','OPERATOR'])->exists(), 403);
        try {
            $client->refresh();
            return redirect()->route('license.status')->with('status', 'Lease lisensi berhasil disinkronkan dengan Publisher.');
        } catch (Throwable $exception) {
            Log::warning('Manual CBT license refresh failed.', ['exception' => get_class($exception)]);
            $code = preg_match('/^[A-Z0-9_]+$/', $exception->getMessage()) ? $exception->getMessage() : 'LICENSE_REFRESH_FAILED';
            return redirect()->route('license.status')->with('license_error', $code);
        }
    }
}
