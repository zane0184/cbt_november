<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\LocalInstallationIdentity;
use App\Models\Tenant;
use App\Services\Licensing\InstallationIdentityService;
use Database\Seeders\SystemTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InstallationIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://identity-test.example.test',
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'license.product_code' => 'CBT-MI',
            'license.product_version' => '1.0.0',
        ]);
    }

    public function test_one_system_tenant_and_one_madrasah_tenant_are_allowed_and_identity_is_idempotent(): void
    {
        SystemTenantSeeder::seed();
        $madrasah = Tenant::factory()->create(['name' => 'Madrasah Tunggal', 'code' => 'MADRASAH-TUNGGAL']);

        $service = app(InstallationIdentityService::class);
        $first = $service->getOrCreate((string) $madrasah->id);
        $second = $service->getOrCreate((string) $madrasah->id);

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame((string) $madrasah->id, (string) $first->tenant_id);
        $this->assertSame(1, LocalInstallationIdentity::query()->count());
        $this->assertSame(2, Tenant::withoutGlobalScopes()->count());
    }

    public function test_zero_madrasah_tenants_are_rejected(): void
    {
        SystemTenantSeeder::seed();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CBT_STANDALONE_REQUIRES_ONE_TENANT');

        app(InstallationIdentityService::class)->getOrCreate('00000000-0000-4000-8000-000000000099');
    }

    public function test_two_madrasah_tenants_are_rejected_even_when_system_tenant_exists(): void
    {
        SystemTenantSeeder::seed();
        $first = Tenant::factory()->create(['code' => 'MADRASAH-1']);
        Tenant::factory()->create(['code' => 'MADRASAH-2']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CBT_STANDALONE_REQUIRES_ONE_TENANT');

        app(InstallationIdentityService::class)->getOrCreate((string) $first->id);
    }

    public function test_missing_or_mismatched_madrasah_tenant_id_is_rejected(): void
    {
        SystemTenantSeeder::seed();
        Tenant::factory()->create(['code' => 'MADRASAH-VALID']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CBT_STANDALONE_REQUIRES_ONE_TENANT');

        app(InstallationIdentityService::class)->getOrCreate('00000000-0000-4000-8000-000000000099');
    }

    public function test_system_tenant_cannot_be_used_as_installation_owner(): void
    {
        SystemTenantSeeder::seed();
        Tenant::factory()->create(['code' => 'MADRASAH-VALID']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('INSTALLATION_TENANT_MISMATCH');

        app(InstallationIdentityService::class)->getOrCreate(Tenant::SYSTEM_TENANT_ID);
    }

    public function test_tenant_scoped_academic_data_remains_invisible_outside_its_madrasah_context(): void
    {
        SystemTenantSeeder::seed();
        $madrasah = Tenant::factory()->create(['code' => 'MADRASAH-ISOLATED']);
        $context = app(TenantContext::class);

        $context->setTenantId((string) $madrasah->id);
        $year = AcademicYear::create(['name' => '2026/2027']);
        $this->assertSame((string) $madrasah->id, (string) $year->tenant_id);

        $context->setTenantId(Tenant::SYSTEM_TENANT_ID);
        $this->assertSame(0, AcademicYear::query()->count());
        $this->assertNull(AcademicYear::query()->find($year->id));

        $context->setTenantId(null);
    }
}
