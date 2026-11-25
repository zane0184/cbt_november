<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RombelStudentPivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_relationship_directions_create_tenant_scoped_uuid_pivot_rows(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenantId($tenant->id);

        $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $profile = StudentProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $studentUser->id,
            'full_name' => 'Siswa Pivot',
            'nisn' => '9000000001',
        ]);

        $rombelA = Rombel::factory()->create(['tenant_id' => $tenant->id]);
        $rombelB = Rombel::factory()->create(['tenant_id' => $tenant->id]);

        $rombelA->students()->attach($profile->id);
        $profile->rombels()->attach($rombelB->id);

        $this->assertDatabaseHas('rombel_student', [
            'tenant_id' => $tenant->id,
            'rombel_id' => $rombelA->id,
            'student_id' => $profile->id,
        ]);
        $this->assertDatabaseHas('rombel_student', [
            'tenant_id' => $tenant->id,
            'rombel_id' => $rombelB->id,
            'student_id' => $profile->id,
        ]);

        $rows = \Illuminate\Support\Facades\DB::table('rombel_student')
            ->where('tenant_id', $tenant->id)
            ->where('student_id', $profile->id)
            ->get();

        $this->assertCount(2, $rows);
        $this->assertNotEmpty($rows[0]->id);
        $this->assertNotEmpty($rows[1]->id);
    }

    public function test_relationship_cannot_attach_student_profile_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        app(TenantContext::class)->setTenantId($tenantA->id);
        $rombel = Rombel::factory()->create(['tenant_id' => $tenantA->id]);

        app(TenantContext::class)->setTenantId($tenantB->id);
        $studentUser = User::factory()->create(['tenant_id' => $tenantB->id]);
        $profile = StudentProfile::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $studentUser->id,
            'full_name' => 'Siswa Tenant B',
            'nisn' => '9000000002',
        ]);

        app(TenantContext::class)->setTenantId($tenantA->id);
        $this->expectException(QueryException::class);
        $rombel->students()->attach($profile->id);
    }
}
