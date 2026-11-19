<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class Fase1bSchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_fase1b_tables_exist_in_database(): void
    {
        $expectedTables = [
            'registered_devices',
            'device_leases',
            'multi_curriculums',
            'multi_curriculum_versions',
            'multi_curriculum_structures',
            'multi_learning_outcomes',
            'question_multi_curriculum_mappings',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist in database.");
        }
    }

    public function test_fencing_columns_exist_on_exam_attempts_table(): void
    {
        $columns = [
            'fence_generation',
            'current_device_token',
            'device_lease_expires_at',
            'last_lease_heartbeat_at',
        ];

        foreach ($columns as $col) {
            $this->assertTrue(Schema::hasColumn('exam_attempts', $col), "Column {$col} should exist on exam_attempts.");
        }
    }

    public function test_registered_devices_foreign_key_and_tenant_isolation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $deviceId = (string) Str::uuid();
        DB::table('registered_devices')->insert([
            'id' => $deviceId,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'installation_uuid' => (string) Str::uuid(),
            'device_token' => 'token_' . Str::random(10),
            'device_token_hash' => hash('sha256', 'test_secret'),
            'platform' => 'ANDROID',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('registered_devices', [
            'id' => $deviceId,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_multi_curriculum_cascade_and_tenant_isolation(): void
    {
        $tenant = Tenant::factory()->create();

        $curriculumId = (string) Str::uuid();
        DB::table('multi_curriculums')->insert([
            'id' => $curriculumId,
            'tenant_id' => $tenant->id,
            'code' => 'KBC_2026',
            'name' => 'Kurikulum Berbasis Cinta 2026',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $versionId = (string) Str::uuid();
        DB::table('multi_curriculum_versions')->insert([
            'id' => $versionId,
            'tenant_id' => $tenant->id,
            'multi_curriculum_id' => $curriculumId,
            'version_code' => 'V1_FINAL',
            'title' => 'Versi 1 Final KBC',
            'effective_year' => 2026,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $structureId = (string) Str::uuid();
        DB::table('multi_curriculum_structures')->insert([
            'id' => $structureId,
            'tenant_id' => $tenant->id,
            'multi_curriculum_version_id' => $versionId,
            'node_type' => 'DIMENSI_CINTA',
            'code' => 'KASIH_ALLAH',
            'title' => 'Dimensi Cinta & Kasih Allah',
            'sort_order' => 1,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outcomeId = (string) Str::uuid();
        DB::table('multi_learning_outcomes')->insert([
            'id' => $outcomeId,
            'tenant_id' => $tenant->id,
            'multi_curriculum_structure_id' => $structureId,
            'multi_curriculum_version_id' => $versionId,
            'code' => 'KBC-001',
            'outcome_type' => 'KBC_CINTA',
            'description' => 'Meneladani Asmaul Husna dalam kehidupan sehari-hari',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('multi_learning_outcomes', [
            'id' => $outcomeId,
            'code' => 'KBC-001',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_question_multi_curriculum_mappings_composite_foreign_keys(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $curriculumId = (string) Str::uuid();
        DB::table('multi_curriculums')->insert([
            'id' => $curriculumId,
            'tenant_id' => $tenant->id,
            'code' => 'KBC_2026',
            'name' => 'Kurikulum Berbasis Cinta 2026',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $versionId = (string) Str::uuid();
        DB::table('multi_curriculum_versions')->insert([
            'id' => $versionId,
            'tenant_id' => $tenant->id,
            'multi_curriculum_id' => $curriculumId,
            'version_code' => 'V1_FINAL',
            'title' => 'Versi 1 Final KBC',
            'effective_year' => 2026,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $structureId = (string) Str::uuid();
        DB::table('multi_curriculum_structures')->insert([
            'id' => $structureId,
            'tenant_id' => $tenant->id,
            'multi_curriculum_version_id' => $versionId,
            'node_type' => 'DIMENSI_CINTA',
            'code' => 'KASIH_ALLAH',
            'title' => 'Dimensi Cinta & Kasih Allah',
            'sort_order' => 1,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outcomeId = (string) Str::uuid();
        DB::table('multi_learning_outcomes')->insert([
            'id' => $outcomeId,
            'tenant_id' => $tenant->id,
            'multi_curriculum_structure_id' => $structureId,
            'code' => 'KBC-001',
            'outcome_type' => 'KBC_CINTA',
            'description' => 'Meneladani Asmaul Husna dalam kehidupan sehari-hari',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $questionId = (string) Str::uuid();
        DB::table('questions')->insert([
            'id' => $questionId,
            'tenant_id' => $tenant->id,
            'owner_type' => 'PERSONAL',
            'owner_user_id' => $user->id,
            'question_code' => 'Q-001',
            'status' => 'PUBLISHED',
            'visibility' => 'PRIVATE',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mappingId = (string) Str::uuid();
        DB::table('question_multi_curriculum_mappings')->insert([
            'id' => $mappingId,
            'tenant_id' => $tenant->id,
            'question_id' => $questionId,
            'multi_curriculum_version_id' => $versionId,
            'multi_learning_outcome_id' => $outcomeId,
            'weight' => 1.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('question_multi_curriculum_mappings', [
            'id' => $mappingId,
            'tenant_id' => $tenant->id,
            'question_id' => $questionId,
            'multi_learning_outcome_id' => $outcomeId,
        ]);
    }

    public function test_device_leases_unique_constraint_on_attempt_and_fence_generation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $attempt = \App\Models\ExamAttempt::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $deviceId = (string) Str::uuid();
        DB::table('registered_devices')->insert([
            'id' => $deviceId,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'installation_uuid' => (string) Str::uuid(),
            'device_token' => 'token_' . Str::random(10),
            'device_token_hash' => hash('sha256', 'test_secret'),
            'platform' => 'ANDROID',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lease1Id = (string) Str::uuid();
        DB::table('device_leases')->insert([
            'id' => $lease1Id,
            'tenant_id' => $tenant->id,
            'exam_attempt_id' => $attempt->id,
            'registered_device_id' => $deviceId,
            'fence_generation' => 1,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $lease2Id = (string) Str::uuid();
        DB::table('device_leases')->insert([
            'id' => $lease2Id,
            'tenant_id' => $tenant->id,
            'exam_attempt_id' => $attempt->id,
            'registered_device_id' => $deviceId,
            'fence_generation' => 1,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
