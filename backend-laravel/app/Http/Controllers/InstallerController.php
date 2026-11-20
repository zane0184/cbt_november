<?php

namespace App\Http\Controllers;

use App\Services\InstallerService;
use App\Services\InstallerStateStore;
use App\Services\Licensing\InstallationIdentityService;
use App\Services\Licensing\PublisherLicenseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallerController extends Controller
{
    public function __construct(protected InstallerService $installerService)
    {
    }

    public function authorizeForm()
    {
        $tokenConfigured = is_string(config('installer.bootstrap_token'))
            && strlen(config('installer.bootstrap_token')) >= 32;

        return response()->view('installer.authorize', compact('tokenConfigured'), 200, [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
        ]);
    }

    public function authorizeBootstrap(Request $request)
    {
        $configuredToken = config('installer.bootstrap_token');
        $providedToken = $request->input('bootstrap_token');

        if (!is_string($configuredToken) || strlen($configuredToken) < 32
            || !is_string($providedToken) || !hash_equals($configuredToken, $providedToken)) {
            return back()->withErrors([
                'bootstrap_token' => 'Token otorisasi tidak valid atau belum dikonfigurasi oleh operator server.',
            ])->withInput(['bootstrap_token' => '']);
        }

        $request->session()->regenerate();
        $request->session()->put('installer.bootstrap_authorized_at', now()->timestamp);

        return redirect()->route('installer.requirements')
            ->with('success', 'Otorisasi operator diterima. Sesi installer berlaku selama 60 menit.');
    }

    public function index()
    {
        return redirect()->route('installer.requirements');
    }

    public function requirements()
    {
        $requirements = $this->installerService->checkRequirements();
        return view('installer.requirements', compact('requirements'));
    }

    public function database()
    {
        if (!$this->installerService->checkRequirements()['is_ready']) {
            return redirect()->route('installer.requirements')->with('error', 'Persyaratan server belum terpenuhi.');
        }

        $defaultConfig = [
            'host' => config('database.connections.mysql.host', '127.0.0.1'),
            'port' => config('database.connections.mysql.port', '3306'),
            'database' => config('database.connections.mysql.database', ''),
            'username' => config('database.connections.mysql.username', ''),
        ];

        return view('installer.database', compact('defaultConfig'));
    }

    public function testDatabase(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string|max:64',
            'username' => 'required|string|max:128',
            'password' => 'nullable|string|max:1024',
        ]);

        $result = $this->installerService->testDatabaseConnection($validated);
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function saveDatabase(Request $request)
    {
        if (!$this->installerService->checkRequirements()['is_ready']) {
            return redirect()->route('installer.requirements')->with('error', 'Persyaratan server belum terpenuhi.');
        }

        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string|max:64',
            'username' => 'required|string|max:128',
            'password' => 'nullable|string|max:1024',
        ]);

        $result = $this->installerService->saveDatabaseConfig($validated);
        if (!$result['success']) {
            return back()->withInput()->with('error', $result['message']);
        }

        return redirect()->route('installer.migrations')->with('success', $result['message']);
    }

    public function migrations()
    {
        $state = $this->installerService->currentState();
        if ($state === 'SCHEMA_READY') {
            return redirect()->route('installer.admin');
        }
        if ($state === 'ADMIN_CREATED') return redirect()->route(config('license.pre_activation_2026') ? 'installer.complete' : 'installer.license');
        if (in_array($state, ['PRE_ACTIVATION_READY', 'LICENSE_ACTIVATED', 'INSTALL_VERIFIED'], true)) return redirect()->route('installer.complete');
        if ($state === 'TENANT_CREATED') {
            return redirect()->route('installer.admin');
        }
        if ($state === 'UNINITIALIZED') {
            return redirect()->route('installer.database');
        }

        $inspection = $this->installerService->inspectCurrentDatabase();
        $hasTables = $inspection['has_tables'];
        $canRunMigrations = $inspection['safe_to_migrate'];
        $databaseStatus = $inspection;
        return view('installer.migrations', compact('hasTables', 'canRunMigrations', 'databaseStatus'));
    }

    public function runMigrations()
    {
        $result = $this->installerService->runMigrations();
        if (!$result['success']) {
            if (request()->expectsJson()) {
                return response()->json($result, 422);
            }
            return back()->with('error', $result['message']);
        }

        if (request()->expectsJson()) {
            return response()->json($result, 200);
        }

        return redirect()->route('installer.admin')->with('success', $result['message']);
    }

    public function admin()
    {
        $state = $this->installerService->currentState();
        if ($state === 'ADMIN_CREATED') return redirect()->route(config('license.pre_activation_2026') ? 'installer.complete' : 'installer.license');
        if (in_array($state, ['PRE_ACTIVATION_READY', 'LICENSE_ACTIVATED', 'INSTALL_VERIFIED'], true)) return redirect()->route('installer.complete');
        if (!in_array($state, ['SCHEMA_READY', 'TENANT_CREATED'], true)) {
            return redirect()->route($state === 'UNINITIALIZED' ? 'installer.database' : 'installer.migrations')
                ->with('error', 'Database belum mencapai tahap SCHEMA_READY atau pemulihan TENANT_CREATED.');
        }

        return view('installer.admin');
    }

    public function saveAdmin(Request $request)
    {
        $validated = $request->validate([
            'madrasah_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_username' => 'required|string|min:3|max:80|regex:/^[A-Za-z0-9._-]+$/',
            'admin_email' => 'nullable|email|max:255',
            'admin_password' => 'required|string|min:14|confirmed',
        ]);

        if (config('license.pre_activation_2026')) {
            try {
                $existingState = app(InstallerStateStore::class)->read();
                $alreadyIssued = !empty($existingState['pre_activation_issued_at']);
                $issueDeadline = \Illuminate\Support\Carbon::parse((string) config('license.pre_activation_issue_deadline'))->utc();
                if (!$alreadyIssued && now()->utc()->greaterThan($issueDeadline)) {
                    return back()->withInput()->with('error', 'Penerbitan mode pilot 2026 telah ditutup pada 31 Desember 2026. Gunakan aktivasi Publisher.');
                }
            } catch (Throwable $exception) {
                Log::error('CBT pilot issue deadline could not be verified.', ['exception' => get_class($exception)]);
                return back()->withInput()->with('error', 'Batas penerbitan mode pilot tidak dapat diverifikasi. Instalasi dihentikan.');
            }
        }

        $currentInstallState = $this->installerService->currentState();
        $result = config('license.pre_activation_2026') && $currentInstallState === 'ADMIN_CREATED'
            ? ['success' => true, 'message' => 'Melanjutkan persiapan identitas pilot untuk Admin Madrasah yang sudah tercatat.']
            : $this->installerService->createInitialDataAndAdmin($validated);
        if (!$result['success']) {
            return back()->withInput()->with('error', $result['message']);
        }

        if (config('license.pre_activation_2026')) {
            try {
                $store = app(InstallerStateStore::class);
                $store->synchronized(function (array $installState) use ($store): void {
                    if (($installState['state'] ?? null) !== 'ADMIN_CREATED' || empty($installState['tenant_id'])) {
                        throw new \RuntimeException('PILOT_INSTALLER_STATE_INVALID');
                    }

                    $identity = app(InstallationIdentityService::class)->getOrCreate((string) $installState['tenant_id']);
                    $issuedAt = !empty($installState['pre_activation_issued_at'])
                        ? \Illuminate\Support\Carbon::parse($installState['pre_activation_issued_at'])->utc()
                        : now()->utc();
                    $expiresAt = !empty($installState['pre_activation_expires_at'])
                        ? \Illuminate\Support\Carbon::parse($installState['pre_activation_expires_at'])->utc()
                        : $issuedAt->copy()->addDays(max(1, (int) config('license.pre_activation_duration_days', 180)));

                    $store->write(array_merge($installState, [
                        'state' => 'PRE_ACTIVATION_READY',
                        'license_mode' => 'PRE_ACTIVATION_2026',
                        'installation_identity_id' => (string) $identity->getKey(),
                        'pre_activation_issued_at' => $issuedAt->toIso8601String(),
                        'pre_activation_expires_at' => $expiresAt->toIso8601String(),
                    ]));
                });
            } catch (Throwable $exception) {
                Log::error('CBT pilot identity preparation failed after Admin creation.', ['exception' => get_class($exception)]);
                return back()->with('error', 'Akun Admin sudah tercatat, tetapi identitas pilot belum siap. Jangan menghapus database atau mengulang migration; hubungi dukungan dengan kode PILOT_IDENTITY_PREPARATION_FAILED.');
            }
        }

        return redirect()->route(config('license.pre_activation_2026') ? 'installer.complete' : 'installer.license')->with('success', $result['message']);
    }

    public function license()
    {
        $stateStore = app(InstallerStateStore::class);
        $installState = $stateStore->read();
        if (in_array(($installState['state'] ?? null), ['LICENSE_ACTIVATED', 'INSTALL_VERIFIED'], true)) return redirect()->route('installer.complete');
        if (($installState['state'] ?? null) !== 'ADMIN_CREATED' || empty($installState['tenant_id'])) {
            return redirect()->route('installer.admin')->with('error', 'Buat profil madrasah dan akun Admin sebelum aktivasi lisensi.');
        }
        $identity = app(InstallationIdentityService::class)->getOrCreate((string) $installState['tenant_id']);
        $existingLicense = \App\Models\LocalLicenseState::query()->where('installation_identity_id', $identity->id)->first();
        if ($existingLicense && $existingLicense->status === 'ACTIVE') {
            try {
                app(\App\Services\Licensing\LicenseArtifactVerifier::class)->assertOperational($existingLicense);
                $stateStore->write(array_merge($installState, [
                    'state' => 'LICENSE_ACTIVATED', 'license_id' => $existingLicense->license_id,
                    'license_revision' => $existingLicense->license_revision,
                    'lease_expires_at' => $existingLicense->lease_expires_at?->toIso8601String(),
                ]));
                return redirect()->route('installer.complete');
            } catch (Throwable) {
                // Keep the installer open for a deliberate reactivation/support path.
            }
        }
        return view('installer.license', compact('identity', 'existingLicense'));
    }

    public function refreshLicense()
    {
        try {
            $licenseState = app(PublisherLicenseClient::class)->refresh();
            $stateStore = app(InstallerStateStore::class);
            $installState = $stateStore->read();
            $stateStore->write(array_merge($installState, [
                'state' => 'LICENSE_ACTIVATED', 'license_id' => $licenseState->license_id,
                'license_revision' => $licenseState->license_revision, 'lease_expires_at' => $licenseState->lease_expires_at?->toIso8601String(),
            ]));
            return redirect()->route('installer.complete')->with('success', 'Lease lisensi berhasil diperbarui.');
        } catch (Throwable $exception) {
            $code = preg_match('/^[A-Z0-9_]+$/', $exception->getMessage()) ? $exception->getMessage() : 'LICENSE_REFRESH_FAILED';
            return back()->with('error', 'Pembaruan lease belum berhasil. Kode: '.$code);
        }
    }

    public function activateLicense(Request $request)
    {
        $data = $request->validate([
            'activation_token' => ['required', 'string', 'min:30', 'max:120'],
            'trusted_key_id' => ['required', 'string', 'max:80'],
            'trusted_public_key' => ['required', 'string', 'max:80'],
        ]);
        $stateStore = app(InstallerStateStore::class);
        $installState = $stateStore->read();
        if (($installState['state'] ?? null) !== 'ADMIN_CREATED' || empty($installState['tenant_id'])) {
            return redirect()->route('installer.license')->with('error', 'Status installer tidak mengizinkan aktivasi lisensi.');
        }
        try {
            $licenseState = app(PublisherLicenseClient::class)->activate(
                (string) $installState['tenant_id'], $data['activation_token'], $data['trusted_key_id'], $data['trusted_public_key']
            );
            $stateStore->write(array_merge($installState, [
                'state' => 'LICENSE_ACTIVATED', 'license_id' => $licenseState->license_id,
                'license_revision' => $licenseState->license_revision, 'lease_expires_at' => $licenseState->lease_expires_at?->toIso8601String(),
            ]));
            return redirect()->route('installer.complete')->with('success', 'Lisensi berhasil diverifikasi dan terikat pada instalasi ini.');
        } catch (Throwable $exception) {
            Log::warning('CBT Publisher activation failed.', ['exception' => get_class($exception)]);
            $code = preg_match('/^[A-Z0-9_]+$/', $exception->getMessage()) ? $exception->getMessage() : 'ACTIVATION_FAILED';
            return back()->with('error', 'Aktivasi belum berhasil. Periksa token, key Publisher, HTTPS, dan koneksi. Kode: '.$code)
                ->withInput(['trusted_key_id' => $data['trusted_key_id'], 'trusted_public_key' => $data['trusted_public_key']]);
        }
    }

    public function complete()
    {
        // Read one authoritative checkpoint so routing cannot mix two different
        // installer states during a concurrent step/recovery operation.
        $status = $this->installerService->completionStatus();
        $state = (string) ($status['state'] ?? 'UNKNOWN');
        $allowedStates = config('license.pre_activation_2026')
            ? ['PRE_ACTIVATION_READY', 'LICENSE_ACTIVATED', 'INSTALL_VERIFIED']
            : ['LICENSE_ACTIVATED', 'INSTALL_VERIFIED'];

        if (!($status['can_finalize'] ?? false) || !in_array($state, $allowedStates, true)) {
            $target = match ($state) {
                'UNINITIALIZED' => 'installer.database',
                'DB_CONFIG_VERIFIED' => 'installer.migrations',
                'SCHEMA_READY', 'TENANT_CREATED' => 'installer.admin',
                'ADMIN_CREATED' => 'installer.license',
                default => 'installer.migrations',
            };

            return redirect()->route($target)->with(
                'error',
                $status['message'] ?? 'Status instalasi belum memenuhi prasyarat finalisasi.'
            );
        }

        return view('installer.complete', compact('status'));
    }

    public function finalize()
    {
        if (!$this->installerService->lockInstaller()) {
            return back()->with('error', 'Verifikasi akhir gagal. Installer belum dikunci; periksa kembali status database, tenant, Admin Madrasah, permission, dan migration ledger.');
        }

        return redirect('/login')->with('success', 'Instalasi CBT Madrasah berhasil diverifikasi dan dikunci. Silakan masuk menggunakan akun Admin Madrasah.');
    }
}
