<?php

namespace App\Http\Controllers;

use App\Models\LocalInstallationIdentity;
use App\Models\LocalLicenseState;
use App\Services\Licensing\PublisherLicenseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class LicenseStatusController extends Controller
{
    public function __invoke(): View
    {
        return view('license.status', [
            'identity' => LocalInstallationIdentity::query()->first(),
            'state' => LocalLicenseState::query()->first(),
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
