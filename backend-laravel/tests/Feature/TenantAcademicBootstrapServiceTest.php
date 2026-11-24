<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\Tenant;
use App\Services\TenantAcademicBootstrapService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class TenantAcademicBootstrapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_provisions_the_existing_mi_phase_and_grade_catalog_for_one_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'MIN Test A', 'code' => 'MINTESTA']);

        $result = app(TenantAcademicBootstrapService::class)->provisionForTenant((string) $tenant->id);

        $this->assertSame(3, $result['phase_count']);
        $this->assertSame(6, $result['grade_level_count']);
        $this->assertSame(6, $result['academic_year_count']);
        $this->assertSame(12, $result['semester_count']);

        $rows = DB::table('grade_levels')
            ->join('phases', function ($join) use ($tenant): void {
                $join->on('phases.id', '=', 'grade_levels.phase_id')
                    ->where('phases.tenant_id', '=', $tenant->id);
            })
            ->where('grade_levels.tenant_id', $tenant->id)
            ->select('grade_levels.code', 'grade_levels.level', 'phases.code as phase_code')
            ->orderBy('grade_levels.level')
            ->get();

        $this->assertCount(6, $rows);
        $this->assertSame(6, DB::table('academic_years')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(12, DB::table('semesters')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(0, DB::table('semesters')->where('tenant_id', $tenant->id)->where('is_active', true)->count());
        $this->assertSame([
            ['MI_1', 1, 'FASE_A'],
            ['MI_2', 2, 'FASE_A'],
            ['MI_3', 3, 'FASE_B'],
            ['MI_4', 4, 'FASE_B'],
            ['MI_5', 5, 'FASE_C'],
            ['MI_6', 6, 'FASE_C'],
        ], $rows->map(fn ($row) => [$row->code, (int) $row->level, $row->phase_code])->all());
    }

    public function test_provisioning_is_idempotent_and_does_not_rewrite_existing_catalog_rows(): void
    {
        $tenant = Tenant::create(['name' => 'MIN Test B', 'code' => 'MINTESTB']);
        $service = app(TenantAcademicBootstrapService::class);

        $first = $service->provisionForTenant((string) $tenant->id);
        $second = $service->provisionForTenant((string) $tenant->id);

        $this->assertSame($first['curriculum_id'], $second['curriculum_id']);
        $this->assertSame($first['phase_ids'], $second['phase_ids']);
        $this->assertSame($first['grade_level_ids'], $second['grade_level_ids']);
        $this->assertSame(1, DB::table('curricula')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(3, DB::table('phases')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(6, DB::table('grade_levels')->where('tenant_id', $tenant->id)->count());
    }

    public function test_each_installation_receives_separate_tenant_owned_academic_rows(): void
    {
        $tenantA = Tenant::create(['name' => 'MIN Isolasi A', 'code' => 'MINISOA']);
        $tenantB = Tenant::create(['name' => 'MIN Isolasi B', 'code' => 'MINISOB']);
        $service = app(TenantAcademicBootstrapService::class);

        $catalogA = $service->provisionForTenant((string) $tenantA->id);
        $catalogB = $service->provisionForTenant((string) $tenantB->id);

        $this->assertNotSame($catalogA['curriculum_id'], $catalogB['curriculum_id']);
        $this->assertNotSame($catalogA['grade_level_ids'][1], $catalogB['grade_level_ids'][1]);
        $this->assertSame(6, DB::table('grade_levels')->where('tenant_id', $tenantA->id)->count());
        $this->assertSame(6, DB::table('grade_levels')->where('tenant_id', $tenantB->id)->count());
    }

    public function test_conflicting_existing_phase_is_rejected_without_overwriting_it(): void
    {
        $tenant = Tenant::create(['name' => 'MIN Conflict', 'code' => 'MINCONFLICT']);
        $context = app(TenantContext::class);
        $previous = $context->getTenantId();
        $context->setTenantId((string) $tenant->id);

        try {
            $curriculum = Curriculum::create([
                'name' => 'Kurikulum Merdeka',
                'code' => 'KURIKULUM_MERDEKA',
                'status' => 'ACTIVE',
                'is_active' => true,
            ]);
            $phase = Phase::create([
                'curriculum_id' => $curriculum->id,
                'name' => 'Fase Salah',
                'code' => 'FASE_A',
                'status' => 'ACTIVE',
            ]);
        } finally {
            $context->setTenantId($previous);
        }

        try {
            app(TenantAcademicBootstrapService::class)->provisionForTenant((string) $tenant->id);
            $this->fail('Conflicting phase mapping must fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('conflicts', $exception->getMessage());
        }

        $this->assertDatabaseHas('phases', [
            'id' => $phase->id,
            'tenant_id' => $tenant->id,
            'name' => 'Fase Salah',
        ]);
        $this->assertDatabaseCount('grade_levels', 0);
    }
}
