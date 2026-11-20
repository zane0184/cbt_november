<?php

namespace Tests\Feature;

use App\Services\InstallerService;
use App\Services\InstallerStateStore;
use App\Services\Licensing\InstallationIdentityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * One-time installer acceptance test for a dedicated empty MySQL 8 database.
 * Never points this test at a production or shared database.
 */
class InstallerEndToEndTest extends TestCase
{
    public function test_clean_installation_runs_from_empty_database_to_locked_state(): void
    {
        if (env('CBT_INSTALLER_E2E') !== '1') {
            $this->markTestSkipped('Set CBT_INSTALLER_E2E=1 only for an isolated disposable database.');
        }

        $host = (string) env('CBT_INSTALLER_E2E_DB_HOST', '127.0.0.1');
        $port = (int) env('CBT_INSTALLER_E2E_DB_PORT', 3306);
        $database = (string) env('CBT_INSTALLER_E2E_DB_DATABASE', '');
        $username = (string) env('CBT_INSTALLER_E2E_DB_USERNAME', '');
        $password = (string) env('CBT_INSTALLER_E2E_DB_PASSWORD', '');

        if (!str_starts_with($database, 'cbt_installer_test_')) {
            $this->fail('Installer E2E database name must start with cbt_installer_test_.');
        }
        if ($database === (string) config('database.connections.mysql.database')) {
            $this->fail('Installer E2E database must differ from the application default database.');
        }

        $service = app(InstallerService::class);
        $this->assertFalse($service->isInstalled(), 'Installer E2E cannot run when this app instance is already installed.');
        $inspection = $service->inspectDatabase(compact('host', 'port', 'database', 'username', 'password'));
        $this->assertTrue($inspection['success'], $inspection['message'] ?? 'Database inspection failed.');
        $this->assertTrue($inspection['is_empty'], 'Disposable database must be empty; this test never drops existing tables.');

        $paths = [
            base_path('.env'),
            storage_path('installed.lock'),
            app(InstallerStateStore::class)->statePath(),
            app(InstallerStateStore::class)->lockPath(),
            base_path('bootstrap/cache/config.php'),
        ];
        $originals = [];
        foreach ($paths as $path) {
            $originals[$path] = is_file($path) ? file_get_contents($path) : null;
        }
        $originalDatabaseConfig = config('database.connections.mysql');
        $originalDefaultConnection = config('database.default');
        $originalAppKey = config('app.key');
        $originalAppInstalled = config('app.installed');
        $originalAppUrl = config('app.url');
        $originalPilotMode = config('license.pre_activation_2026');
        $originalPilotDeadline = config('license.pre_activation_issue_deadline');
        $originalPilotDays = config('license.pre_activation_duration_days');
        $store = app(InstallerStateStore::class);

        try {
            Carbon::setTestNow(Carbon::parse('2026-11-20T12:00:00Z'));
            config([
                'app.url' => 'https://cbt-installer-e2e.example.test',
                'license.pre_activation_2026' => true,
                'license.pre_activation_issue_deadline' => '2026-12-31T23:59:59+07:00',
                'license.pre_activation_duration_days' => 180,
            ]);
            app('request')->server->set('HTTPS', 'on');
            app('request')->server->set('SERVER_PORT', 443);

            $save = $service->saveDatabaseConfig(compact('host', 'port', 'database', 'username', 'password'));
            $this->assertTrue($save['success'], $save['message']);

            $migration = $service->runMigrations();
            $this->assertTrue($migration['success'], $migration['message']);

            $suffix = strtoupper(bin2hex(random_bytes(3)));
            $created = $service->createInitialDataAndAdmin([
                'madrasah_name' => 'CBT Installer E2E Test',
                'madrasah_code' => 'CBTTEST' . $suffix,
                'admin_name' => 'Installer E2E Admin',
                'admin_email' => 'installer-e2e-' . strtolower($suffix) . '@example.test',
                'admin_password' => 'E2E-Password-' . $suffix . '!',
            ]);
            $this->assertTrue($created['success'], $created['message']);

            $identity = app(InstallationIdentityService::class)->getOrCreate((string) $created['tenant_id']);
            $issuedAt = Carbon::now('UTC');
            $expiresAt = $issuedAt->copy()->addDays(180);
            $pilotState = array_merge($store->read(), [
                'state' => 'PRE_ACTIVATION_READY',
                'license_mode' => 'PRE_ACTIVATION_2026',
                'installation_identity_id' => (string) $identity->getKey(),
                'pre_activation_issued_at' => $issuedAt->toIso8601String(),
                'pre_activation_expires_at' => $expiresAt->toIso8601String(),
            ]);
            $store->write($pilotState);

            $firstVerification = $service->verifyInstallationPostconditions($store->read());
            $this->assertTrue($firstVerification['success'], $firstVerification['message'] ?? 'Initial pilot verification failed.');

            // Simulate a failure after the state advanced to INSTALL_VERIFIED.
            // A retry must remain on the pilot path rather than falling into Publisher verification.
            $retryState = array_merge($store->read(), ['state' => 'INSTALL_VERIFIED']);
            $store->write($retryState);
            $retryVerification = $service->verifyInstallationPostconditions($store->read());
            $this->assertTrue($retryVerification['success'], $retryVerification['message'] ?? 'Pilot finalization retry verification failed.');

            $this->assertTrue($service->lockInstaller(), 'Final verification and installer lock must succeed.');
            $this->assertTrue($service->isInstalled());
            $this->assertFileExists(storage_path('installed.lock'));
            $lock = json_decode((string) file_get_contents(storage_path('installed.lock')), true, 32, JSON_THROW_ON_ERROR);
            $this->assertSame('PRE_ACTIVATION_2026', $lock['license_mode'] ?? null);
            $this->assertSame($expiresAt->toIso8601String(), $lock['pre_activation_expires_at'] ?? null);
            $this->assertSame('LOCKED', $store->read()['state'] ?? null);
        } finally {
            Carbon::setTestNow();
            DB::purge('mysql');
            config([
                'database.default' => $originalDefaultConnection,
                'database.connections.mysql' => $originalDatabaseConfig,
                'app.key' => $originalAppKey,
                'app.installed' => $originalAppInstalled,
                'app.url' => $originalAppUrl,
                'license.pre_activation_2026' => $originalPilotMode,
                'license.pre_activation_issue_deadline' => $originalPilotDeadline,
                'license.pre_activation_duration_days' => $originalPilotDays,
            ]);
            DB::purge('mysql');

            foreach ($originals as $path => $contents) {
                if ($contents === null) {
                    if (is_file($path)) {
                        @unlink($path);
                    }
                    continue;
                }
                $directory = dirname($path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0700, true);
                }
                $temporary = tempnam($directory, '.cbt-e2e-restore-');
                file_put_contents($temporary, $contents, LOCK_EX);
                @chmod($temporary, 0600);
                rename($temporary, $path);
            }
            DB::purge('mysql');
        }
    }
}
