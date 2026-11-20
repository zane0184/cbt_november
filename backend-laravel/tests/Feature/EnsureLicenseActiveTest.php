<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLicenseActive;
use App\Models\LocalInstallationIdentity;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InstallerStateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnsureLicenseActiveTest extends TestCase
{
    use RefreshDatabase;

    private array $installerFileBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([app(InstallerStateStore::class)->statePath(), storage_path('installed.lock')] as $path) {
            $this->installerFileBackup[$path] = is_file($path) ? file_get_contents($path) : null;
            if (is_file($path)) @unlink($path);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach ($this->installerFileBackup as $path => $contents) {
            if ($contents === null) {
                if (is_file($path)) @unlink($path);
                continue;
            }
            $directory = dirname($path);
            if (!is_dir($directory)) @mkdir($directory, 0700, true);
            file_put_contents($path, $contents, LOCK_EX);
        }
        parent::tearDown();
    }

    public function test_explicit_test_bypass_works_only_in_testing_environment(): void
    {
        config([
            'license.testing_bypass' => true,
            'license.pre_activation_2026' => false,
        ]);

        $request = Request::create('/api/v1/exams', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = app(EnsureLicenseActive::class)->handle(
            $request,
            fn () => response()->json(['success' => true])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['success' => true], $response->getData(true));
    }

    public function test_license_gate_fails_closed_when_test_bypass_is_disabled(): void
    {
        config([
            'license.testing_bypass' => false,
            'license.pre_activation_2026' => false,
        ]);

        $request = Request::create('/api/v1/exams', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = app(EnsureLicenseActive::class)->handle(
            $request,
            fn () => response()->json(['success' => true])
        );

        $this->assertSame(423, $response->getStatusCode());
        $this->assertSame('LICENSE_NOT_ACTIVATED', $response->getData(true)['code']);
    }

    public function test_valid_pilot_installation_passes_license_middleware(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20T12:00:00Z'));
        $request = $this->pilotRequest('2027-05-19T11:00:00+00:00');

        $response = app(EnsureLicenseActive::class)->handle(
            $request,
            fn () => response()->json(['success' => true])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['success' => true], $response->getData(true));
    }

    public function test_expired_pilot_installation_is_blocked_with_specific_code(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-05-19T11:00:00Z'));
        $request = $this->pilotRequest('2027-05-19T11:00:00+00:00');

        $response = app(EnsureLicenseActive::class)->handle(
            $request,
            fn () => response()->json(['success' => true])
        );

        $this->assertSame(423, $response->getStatusCode());
        $this->assertSame('PRE_ACTIVATION_EXPIRED', $response->getData(true)['code']);
    }

    private function pilotRequest(string $expiresAt): Request
    {
        config([
            'license.testing_bypass' => false,
            'license.pre_activation_2026' => true,
            'license.pre_activation_issue_deadline' => '2026-12-31T23:59:59+07:00',
            'license.pre_activation_duration_days' => 180,
        ]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $identity = LocalInstallationIdentity::query()->create([
            'tenant_id' => $tenant->id,
            'installation_id' => (string) \Illuminate\Support\Str::uuid(),
            'key_id' => 'pilot-test-key',
            'public_key' => 'pilot-test-public-key',
            'encrypted_private_key' => 'pilot-test-encrypted-private-key',
            'key_fingerprint' => hash('sha256', 'pilot-test-public-key'),
            'registered_domain' => 'pilot.example.test',
            'product_code' => 'CBT-MI',
            'product_version' => '1.0.0',
        ]);

        $issuedAt = '2026-11-20T11:00:00+00:00';
        $fingerprint = str_repeat('a', 64);
        $install = [
            'state' => 'LOCKED',
            'license_mode' => 'PRE_ACTIVATION_2026',
            'tenant_id' => (string) $tenant->id,
            'admin_id' => (string) $user->id,
            'installation_identity_id' => (string) $identity->id,
            'schema_fingerprint' => $fingerprint,
            'license_id' => null,
            'license_revision' => null,
            'pre_activation_issued_at' => $issuedAt,
            'pre_activation_expires_at' => $expiresAt,
        ];
        $lock = [
            'license_mode' => 'PRE_ACTIVATION_2026',
            'tenant_id' => (string) $tenant->id,
            'admin_id' => (string) $user->id,
            'installation_identity_id' => (string) $identity->id,
            'schema_fingerprint' => $fingerprint,
            'license_id' => null,
            'license_revision' => null,
            'pre_activation_issued_at' => $issuedAt,
            'pre_activation_expires_at' => $expiresAt,
        ];

        app(InstallerStateStore::class)->write($install);
        file_put_contents(storage_path('installed.lock'), json_encode($lock, JSON_THROW_ON_ERROR), LOCK_EX);

        $request = Request::create('/api/v1/exams', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
