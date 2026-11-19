<?php

namespace Tests\Feature;

use App\Models\MultiCurriculum;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\MultiLearningOutcome;
use App\Models\Question;
use App\Models\QuestionMultiCurriculumMapping;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\User;
use Database\Seeders\SystemTenantSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class Fase1cMultiCurriculumIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_fase1c_migration_up_is_idempotent_when_constraints_already_exist(): void
    {
        $migration = require database_path('migrations/2026_11_05_200001_fase1c_multi_curriculum_integrity.php');

        // The migration chain has already applied Fase1C. Re-running up() must
        // preserve existing indexes/FKs instead of creating duplicate names.
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasIndex('multi_curriculums', 'uniq_m_curr_id_tenant', 'unique'));
        $this->assertTrue(Schema::hasForeignKey('question_multi_curriculum_mappings', 'fk_qmcm_reg_triplet'));
        $this->assertTrue(Schema::hasColumn('question_multi_curriculum_mappings', 'curriculum_owner_id'));
    }

    public function test_system_tenant_seeder_condition_1_creates_record(): void
    {
        $tenant = SystemTenantSeeder::seed();

        $this->assertEquals(Tenant::SYSTEM_TENANT_ID, $tenant->id);
        $this->assertEquals(Tenant::SYSTEM_TENANT_CODE, $tenant->code);
        $this->assertDatabaseHas('tenants', [
            'id' => Tenant::SYSTEM_TENANT_ID,
            'code' => Tenant::SYSTEM_TENANT_CODE,
        ]);
    }

    public function test_system_tenant_seeder_condition_2_noop_on_existing_matching_record(): void
    {
        $tenant1 = SystemTenantSeeder::seed();
        $tenant2 = SystemTenantSeeder::seed();

        $this->assertEquals($tenant1->id, $tenant2->id);
        $this->assertEquals(1, Tenant::where('id', Tenant::SYSTEM_TENANT_ID)->count());
    }

    public function test_system_tenant_seeder_condition_3_halts_if_uuid_exists_with_different_code(): void
    {
        Tenant::create([
            'id' => Tenant::SYSTEM_TENANT_ID,
            'name' => 'Conflict Tenant',
            'code' => 'DIFFERENT_CODE',
            'status' => 'ACTIVE',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/System Tenant Seeding Halted/i');

        SystemTenantSeeder::seed();
    }

    public function test_system_tenant_seeder_condition_4_halts_if_code_exists_with_different_uuid(): void
    {
        Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Conflict Tenant Code',
            'code' => Tenant::SYSTEM_TENANT_CODE,
            'status' => 'ACTIVE',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/System Tenant Seeding Halted/i');

        SystemTenantSeeder::seed();
    }

    public function test_tenant_curriculum_registration_allows_global_and_same_tenant_curriculum(): void
    {
        $systemTenant = SystemTenantSeeder::seed();
        $tenantA = Tenant::factory()->create();

        // 1. Global curriculum owned by System Tenant
        $globalCurriculum = MultiCurriculum::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'code' => 'MERDEKA_2024',
            'name' => 'Kurikulum Merdeka 2024',
            'status' => 'ACTIVE',
        ]);

        $globalVersion = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_id' => $globalCurriculum->id,
            'version_code' => 'V1',
            'title' => 'Versi 1 Global Merdeka',
            'status' => 'ACTIVE',
        ]);

        // Tenant A registers Global Curriculum
        $regGlobal = TenantCurriculumRegistration::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantA->id,
            'multi_curriculum_version_id' => $globalVersion->id,
            'curriculum_owner_id' => $systemTenant->id,
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('tenant_curriculum_registrations', [
            'id' => $regGlobal->id,
            'tenant_id' => $tenantA->id,
            'curriculum_owner_id' => $systemTenant->id,
        ]);

        // 2. Custom curriculum owned by Tenant A
        $customCurriculum = MultiCurriculum::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantA->id,
            'code' => 'CUSTOM_KBC_A',
            'name' => 'Kurikulum KBC Tenant A',
            'status' => 'ACTIVE',
        ]);

        $customVersion = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantA->id,
            'multi_curriculum_id' => $customCurriculum->id,
            'version_code' => 'V1_A',
            'title' => 'Versi 1 Custom A',
            'status' => 'ACTIVE',
        ]);

        // Tenant A registers Custom Curriculum A
        $regCustom = TenantCurriculumRegistration::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantA->id,
            'multi_curriculum_version_id' => $customVersion->id,
            'curriculum_owner_id' => $tenantA->id,
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('tenant_curriculum_registrations', [
            'id' => $regCustom->id,
            'tenant_id' => $tenantA->id,
            'curriculum_owner_id' => $tenantA->id,
        ]);
    }

    public function test_tenant_curriculum_registration_rejects_cross_tenant_ownership(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        // Custom curriculum owned by Tenant B
        $customCurriculumB = MultiCurriculum::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantB->id,
            'code' => 'CUSTOM_B',
            'name' => 'Kurikulum Khusus Tenant B',
            'status' => 'ACTIVE',
        ]);

        $customVersionB = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantB->id,
            'multi_curriculum_id' => $customCurriculumB->id,
            'version_code' => 'V1_B',
            'title' => 'Versi 1 Custom B',
            'status' => 'ACTIVE',
        ]);

        // Tenant A tries to register Tenant B's custom curriculum
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid curriculum registration/i');

        TenantCurriculumRegistration::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantA->id,
            'multi_curriculum_version_id' => $customVersionB->id,
            'curriculum_owner_id' => $tenantB->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_structure_parent_child_version_alignment(): void
    {
        Schema::enableForeignKeyConstraints();
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $systemTenant = SystemTenantSeeder::seed();

        $curriculum = MultiCurriculum::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'code' => 'KBC_SYS',
            'name' => 'KBC System',
            'status' => 'ACTIVE',
        ]);

        $version1 = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_id' => $curriculum->id,
            'version_code' => 'V1',
            'title' => 'Versi 1',
            'status' => 'ACTIVE',
        ]);

        $version2 = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_id' => $curriculum->id,
            'version_code' => 'V2',
            'title' => 'Versi 2',
            'status' => 'ACTIVE',
        ]);

        $parentStruct = MultiCurriculumStructure::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_version_id' => $version1->id,
            'node_type' => 'DIMENSI',
            'code' => 'DIM_1',
            'title' => 'Dimensi 1',
            'status' => 'ACTIVE',
        ]);

        // Same version child -> SUCCESS
        $childStructSameVer = MultiCurriculumStructure::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_version_id' => $version1->id,
            'parent_id' => $parentStruct->id,
            'node_type' => 'SUB_DIMENSI',
            'code' => 'SUB_1',
            'title' => 'Sub Dimensi 1',
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('multi_curriculum_structures', [
            'id' => $childStructSameVer->id,
            'parent_id' => $parentStruct->id,
            'multi_curriculum_version_id' => $version1->id,
        ]);

        // Cross-version parent-child -> REJECTED by DB composite FK
        $this->expectException(QueryException::class);

        DB::table('multi_curriculum_structures')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_version_id' => $version2->id, // Different version!
            'parent_id' => $parentStruct->id,
            'node_type' => 'SUB_DIMENSI',
            'code' => 'SUB_CROSS',
            'title' => 'Sub Cross Version',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_question_multi_curriculum_mapping_registration_triplet_enforcement(): void
    {
        $systemTenant = SystemTenantSeeder::seed();
        $tenantA = Tenant::factory()->create();
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

        $globalCurriculum = MultiCurriculum::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'code' => 'GLOBAL_CURR',
            'name' => 'Global Curriculum',
            'status' => 'ACTIVE',
        ]);

        $globalVersion = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_id' => $globalCurriculum->id,
            'version_code' => 'V1',
            'title' => 'Global Version 1',
            'status' => 'ACTIVE',
        ]);

        $structure = MultiCurriculumStructure::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_version_id' => $globalVersion->id,
            'node_type' => 'ELEMEN',
            'code' => 'E1',
            'title' => 'Elemen 1',
            'status' => 'ACTIVE',
        ]);

        $outcome = MultiLearningOutcome::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_structure_id' => $structure->id,
            'multi_curriculum_version_id' => $globalVersion->id,
            'code' => 'LO-101',
            'outcome_type' => 'CP',
            'description' => 'Target Capaian Pembelajaran 101',
            'status' => 'ACTIVE',
        ]);

        app(\App\Contexts\TenantContext::class)->setTenantId($tenantA->id);

        $question = Question::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantA->id,
            'owner_type' => 'PERSONAL',
            'created_by' => $userA->id,
            'status' => 'PUBLISHED',
        ]);

        // Register curriculum for Tenant A
        TenantCurriculumRegistration::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantA->id,
            'multi_curriculum_version_id' => $globalVersion->id,
            'curriculum_owner_id' => $systemTenant->id,
            'status' => 'ACTIVE',
        ]);

        // Mapping AFTER registration -> SUCCESS
        $mappingId = (string) Str::uuid();
        DB::table('question_multi_curriculum_mappings')->insert([
            'id' => $mappingId,
            'tenant_id' => $tenantA->id,
            'question_id' => $question->id,
            'multi_curriculum_version_id' => $globalVersion->id,
            'multi_learning_outcome_id' => $outcome->id,
            'curriculum_owner_id' => $systemTenant->id,
            'weight' => 1.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('question_multi_curriculum_mappings', [
            'id' => $mappingId,
            'tenant_id' => $tenantA->id,
            'question_id' => $question->id,
            'curriculum_owner_id' => $systemTenant->id,
        ]);
    }

    public function test_ambiguous_data_protection_and_no_blanket_backfill(): void
    {
        // Insert record without provenance (tenant_id is NULL)
        $ambiguousId = (string) Str::uuid();
        DB::table('multi_curriculums')->insert([
            'id' => $ambiguousId,
            'tenant_id' => null,
            'code' => 'LEGACY_AMBIGUOUS',
            'name' => 'Data Kurikulum Lama Ambigu',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seeder / Migration must NOT mutate tenant_id IS NULL to System Tenant automatically
        SystemTenantSeeder::seed();

        $ambiguousRecord = DB::table('multi_curriculums')->where('id', $ambiguousId)->first();
        $this->assertNotNull($ambiguousRecord);
        $this->assertNull($ambiguousRecord->tenant_id, "Ambiguous legacy data must remain untouched without blanket backfill.");
    }

    public function test_legacy_mapping_data_preserved_intact_after_migration(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);

        $curriculum = MultiCurriculum::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'code' => 'LEGACY_CURR',
            'name' => 'Legacy Curriculum',
            'status' => 'ACTIVE',
        ]);

        $version = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'multi_curriculum_id' => $curriculum->id,
            'version_code' => 'V1',
            'title' => 'Legacy Version 1',
            'status' => 'ACTIVE',
        ]);

        $structure = MultiCurriculumStructure::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'multi_curriculum_version_id' => $version->id,
            'node_type' => 'ELEMEN',
            'code' => 'E_LEGACY',
            'title' => 'Legacy Element',
            'status' => 'ACTIVE',
        ]);

        $outcome = MultiLearningOutcome::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'multi_curriculum_structure_id' => $structure->id,
            'multi_curriculum_version_id' => $version->id,
            'code' => 'LO_LEGACY',
            'outcome_type' => 'CP',
            'description' => 'Legacy Outcome',
            'status' => 'ACTIVE',
        ]);

        TenantCurriculumRegistration::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'multi_curriculum_version_id' => $version->id,
            'curriculum_owner_id' => $tenant->id,
            'status' => 'ACTIVE',
        ]);

        $question = Question::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'owner_type' => 'PERSONAL',
            'created_by' => $user->id,
            'status' => 'PUBLISHED',
        ]);

        // Recreate the legacy schema in-place. CREATE TABLE AS SELECT is not
        // suitable here: MySQL may derive a different character set/collation
        // for copied UUID columns, which makes the later composite FK invalid.
        // Keep the original table definition and remove only the Fase1C additions.
        if (Schema::hasColumn('question_multi_curriculum_mappings', 'curriculum_owner_id')) {
            foreach (['fk_qmcm_lo_ver', 'fk_qmcm_lo_owner', 'fk_qmcm_reg_triplet'] as $foreignKey) {
                if (Schema::hasForeignKey('question_multi_curriculum_mappings', $foreignKey)) {
                    Schema::table('question_multi_curriculum_mappings', function (Blueprint $table) use ($foreignKey): void {
                        $table->dropForeign($foreignKey);
                    });
                }
            }

            Schema::table('question_multi_curriculum_mappings', function (Blueprint $table): void {
                $table->dropColumn('curriculum_owner_id');
            });

            Schema::table('question_multi_curriculum_mappings', function (Blueprint $table): void {
                $table->foreign(['multi_learning_outcome_id', 'tenant_id'], 'qmcm_lo_tenant_fk')
                    ->references(['id', 'tenant_id'])
                    ->on('multi_learning_outcomes')
                    ->onDelete('cascade');
            });
        }

        $mappingId = (string) Str::uuid();
        DB::table('question_multi_curriculum_mappings')->insert([
            'id' => $mappingId,
            'tenant_id' => $tenant->id,
            'question_id' => $question->id,
            'multi_curriculum_version_id' => $version->id,
            'multi_learning_outcome_id' => $outcome->id,
            'weight' => 2.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Count and contents before migration
        $countBefore = DB::table('question_multi_curriculum_mappings')->count();
        $recordBefore = DB::table('question_multi_curriculum_mappings')->where('id', $mappingId)->first();
        $this->assertFalse(property_exists($recordBefore, 'curriculum_owner_id'));

        // Execute migration up()
        $migration = require database_path('migrations/2026_11_05_200001_fase1c_multi_curriculum_integrity.php');
        $migration->up();

        // Verify count and contents after migration remain 100% identical and reconciled
        $countAfter = DB::table('question_multi_curriculum_mappings')->count();
        $recordAfter = DB::table('question_multi_curriculum_mappings')->where('id', $mappingId)->first();

        $this->assertEquals($countBefore, $countAfter);
        $this->assertEquals($recordBefore->id, $recordAfter->id);
        $this->assertEquals($recordBefore->tenant_id, $recordAfter->tenant_id);
        $this->assertEquals($recordBefore->question_id, $recordAfter->question_id);
        $this->assertEquals($recordBefore->multi_curriculum_version_id, $recordAfter->multi_curriculum_version_id);
        $this->assertEquals($recordBefore->multi_learning_outcome_id, $recordAfter->multi_learning_outcome_id);
        $this->assertEquals($recordBefore->weight, $recordAfter->weight);
        $this->assertEquals($tenant->id, $recordAfter->curriculum_owner_id);
    }

    public function test_null_curriculum_owner_id_rejected_on_model(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);

        $question = Question::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'owner_type' => 'PERSONAL',
            'created_by' => $user->id,
            'status' => 'PUBLISHED',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/requires a valid non-null curriculum_owner_id/i');

        QuestionMultiCurriculumMapping::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'question_id' => $question->id,
            'multi_curriculum_version_id' => (string) Str::uuid(),
            'multi_learning_outcome_id' => (string) Str::uuid(),
            'curriculum_owner_id' => null, // Explicitly NULL -> Must be rejected!
            'weight' => 1.00,
        ]);
    }

    public function test_legacy_outcome_safely_reconciled_from_structure(): void
    {
        $systemTenant = SystemTenantSeeder::seed();

        $curriculum = MultiCurriculum::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'code' => 'RECON_CURR',
            'name' => 'Reconciliation Curriculum',
            'status' => 'ACTIVE',
        ]);

        $version = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_id' => $curriculum->id,
            'version_code' => 'V1',
            'title' => 'Reconciliation Version 1',
            'status' => 'ACTIVE',
        ]);

        $structure = MultiCurriculumStructure::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_version_id' => $version->id,
            'node_type' => 'ELEMEN',
            'code' => 'E_RECON',
            'title' => 'Reconciliation Element',
            'status' => 'ACTIVE',
        ]);

        $outcomeId = (string) Str::uuid();
        DB::table('multi_learning_outcomes')->insert([
            'id' => $outcomeId,
            'tenant_id' => $systemTenant->id,
            'multi_curriculum_structure_id' => $structure->id,
            'multi_curriculum_version_id' => null, // Omitted version
            'code' => 'LO_RECON',
            'outcome_type' => 'CP',
            'description' => 'Outcome for reconciliation',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run reconciliation SQL query
        DB::statement("
            UPDATE multi_learning_outcomes
            SET multi_curriculum_version_id = (
                SELECT multi_curriculum_version_id
                FROM multi_curriculum_structures
                WHERE multi_curriculum_structures.id = multi_learning_outcomes.multi_curriculum_structure_id
            )
            WHERE multi_curriculum_version_id IS NULL AND multi_curriculum_structure_id IS NOT NULL
        ");

        $reconciledOutcome = DB::table('multi_learning_outcomes')->where('id', $outcomeId)->first();
        $this->assertNotNull($reconciledOutcome);
        $this->assertEquals($version->id, $reconciledOutcome->multi_curriculum_version_id);
    }

    public function test_ambiguous_outcome_without_version_reconciles_safely(): void
    {
        $tenant = Tenant::factory()->create();

        $curriculum = MultiCurriculum::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'code' => 'CURR_SAFE',
            'name' => 'Safe Curriculum',
            'status' => 'ACTIVE',
        ]);

        $version = MultiCurriculumVersion::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'multi_curriculum_id' => $curriculum->id,
            'version_code' => 'V1',
            'title' => 'Version 1',
            'status' => 'ACTIVE',
        ]);

        $struct = MultiCurriculumStructure::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'multi_curriculum_version_id' => $version->id,
            'node_type' => 'ELEMEN',
            'code' => 'E_SAFE',
            'title' => 'Safe Element',
            'status' => 'ACTIVE',
        ]);

        $outcomeId = (string) Str::uuid();
        DB::table('multi_learning_outcomes')->insert([
            'id' => $outcomeId,
            'tenant_id' => $tenant->id,
            'multi_curriculum_structure_id' => $struct->id,
            'multi_curriculum_version_id' => null, // NULL version
            'code' => 'LO_UNRECONCILED',
            'outcome_type' => 'CP',
            'description' => 'Unreconciled outcome before migration',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run reconciliation
        DB::statement("
            UPDATE multi_learning_outcomes
            SET multi_curriculum_version_id = (
                SELECT multi_curriculum_version_id
                FROM multi_curriculum_structures
                WHERE multi_curriculum_structures.id = multi_learning_outcomes.multi_curriculum_structure_id
            )
            WHERE multi_curriculum_version_id IS NULL AND multi_curriculum_structure_id IS NOT NULL
        ");

        $outcomeAfter = DB::table('multi_learning_outcomes')->where('id', $outcomeId)->first();
        $this->assertEquals($version->id, $outcomeAfter->multi_curriculum_version_id);
    }

    public function test_rollback_is_explicitly_blocked_without_schema_changes(): void
    {
        $migration = require database_path('migrations/2026_11_05_200001_fase1c_multi_curriculum_integrity.php');
        $this->assertTrue(Schema::hasTable('tenant_curriculum_registrations'));
        $columnsBefore = Schema::getColumnListing('question_multi_curriculum_mappings');

        try {
            $migration->down();
            $this->fail('Fase1C is forward-only and must reject generic rollback.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Fase1C migration is intentionally irreversible. No schema changes were made. Use a reviewed forward migration or restore a verified backup.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(Schema::hasTable('tenant_curriculum_registrations'));
        $this->assertSame($columnsBefore, Schema::getColumnListing('question_multi_curriculum_mappings'));
    }

    public function test_rollback_does_not_delete_foreign_metadata(): void
    {
        // System Tenant record with code SYSTEM_GLOBAL exists
        $systemTenant = SystemTenantSeeder::seed();

        // Alter tenant code to simulate corrupted/foreign metadata
        DB::table('tenants')->where('id', $systemTenant->id)->update(['code' => 'FOREIGN_METADATA']);

        $migration = require database_path('migrations/2026_11_05_200001_fase1c_multi_curriculum_integrity.php');

        try {
            $migration->down();
            $this->fail('Fase1C is forward-only and must reject generic rollback.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Fase1C migration is intentionally irreversible. No schema changes were made. Use a reviewed forward migration or restore a verified backup.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('tenants', [
            'id' => $systemTenant->id,
            'code' => 'FOREIGN_METADATA',
        ]);
    }
}
