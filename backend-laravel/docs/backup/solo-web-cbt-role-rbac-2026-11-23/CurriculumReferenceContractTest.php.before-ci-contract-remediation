<?php

namespace Tests\Feature;

use App\Models\CurriculumReferenceSource;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurriculumReferenceContractTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function migration_creates_versioned_curriculum_and_assessment_contract_tables(): void
    {
        $this->assertTrue(Schema::hasTable('curriculum_reference_sources'));
        $this->assertTrue(Schema::hasTable('assessment_taxonomy_options'));
        $this->assertTrue(Schema::hasTable('material_topics'));
        $this->assertTrue(Schema::hasTable('question_version_curriculum_mappings'));
        $this->assertTrue(Schema::hasTable('question_version_structure_mappings'));
        $this->assertTrue(Schema::hasTable('tenant_curriculum_subject_mappings'));
        $this->assertTrue(Schema::hasTable('tenant_learning_outcomes'));
        $this->assertTrue(Schema::hasTable('tenant_learning_outcome_structure_mappings'));
        $this->assertTrue(Schema::hasTable('question_version_tenant_outcome_mappings'));

        $this->assertTrue(Schema::hasColumn('multi_curriculum_versions', 'source_id'));
        $this->assertTrue(Schema::hasColumn('multi_curriculum_structures', 'verification_status'));
        $this->assertTrue(Schema::hasColumn('multi_learning_outcomes', 'source_checksum'));
        $this->assertTrue(Schema::hasColumn('assessments', 'assessment_function_option_id'));
        $this->assertTrue(Schema::hasColumn('assessments', 'curriculum_context_snapshot'));
        $this->assertTrue(Schema::hasColumn('assessments', 'assessment_origin'));
        $this->assertTrue(Schema::hasColumn('assessments', 'external_program_name'));
        $this->assertTrue(Schema::hasColumn('exams', 'assessment_context_snapshot'));
        $this->assertTrue(Schema::hasColumn('exam_question_snapshots', 'curriculum_context_snapshot'));
        $this->assertTrue(Schema::hasColumn('teacher_subject_assignments', 'tenant_curriculum_registration_id'));
        $this->assertTrue(Schema::hasColumn('teacher_subject_assignments', 'curriculum_context_snapshot'));
    }

    /** @test */
    public function database_seeder_registers_official_reference_metadata_without_faking_artifact_checksums(): void
    {
        $this->seed(DatabaseSeeder::class);

        $kma = CurriculumReferenceSource::query()
            ->where('document_number', 'KMA 1503 Tahun 2025')
            ->firstOrFail();

        $this->assertSame('METADATA_VERIFIED', $kma->verification_status);
        $this->assertNull($kma->checksum_sha256);
        $this->assertFalse($kma->metadata['artifact_checksum_verified']);

        $kbc = CurriculumReferenceSource::query()
            ->where('document_number', 'Kepdirjen Pendis 6077 Tahun 2025')
            ->firstOrFail();

        $this->assertSame('METADATA_VERIFIED', $kbc->verification_status);

        $systemTenantId = \App\Models\Tenant::SYSTEM_TENANT_ID;
        $this->assertSame(5, \App\Models\MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->where('node_type', 'PANCA_CINTA')
            ->count());
        $this->assertSame(4, \App\Models\MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->where('node_type', 'PM_PRINCIPLE')
            ->count());
        $this->assertSame(4, \App\Models\MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->where('node_type', 'PM_OLAH')
            ->count());
        $this->assertSame(4, \App\Models\MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->where('node_type', 'PM_FRAMEWORK')
            ->count());
        $this->assertSame(8, \App\Models\MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->where('node_type', 'DIMENSI_PROFIL_LULUSAN')
            ->count());

        $religionCp = CurriculumReferenceSource::query()
            ->where('document_number', 'Kepdirjen Pendis 9941 Tahun 2025')
            ->firstOrFail();

        $this->assertSame('PENDING', $religionCp->verification_status);

        $this->assertDatabaseHas('assessment_taxonomy_options', [
            'tenant_id' => \App\Models\Tenant::SYSTEM_TENANT_ID,
            'dimension' => 'FUNCTION',
            'code' => 'FORMATIF',
            'is_selectable' => true,
        ]);
        $this->assertDatabaseHas('assessment_taxonomy_options', [
            'tenant_id' => \App\Models\Tenant::SYSTEM_TENANT_ID,
            'dimension' => 'FUNCTION',
            'code' => 'SUMATIF',
            'is_selectable' => true,
        ]);
        $this->assertDatabaseHas('assessment_taxonomy_options', [
            'tenant_id' => \App\Models\Tenant::SYSTEM_TENANT_ID,
            'dimension' => 'INSTRUMENT',
            'code' => 'OBSERVASI',
            'is_selectable' => true,
        ]);
        $this->assertDatabaseMissing('assessment_taxonomy_options', ['code' => 'UTS']);
        $this->assertDatabaseMissing('assessment_taxonomy_options', ['code' => 'UAS']);
        $this->assertDatabaseMissing('assessment_taxonomy_options', ['code' => 'UM']);
        $this->assertDatabaseMissing('assessment_taxonomy_options', ['code' => 'SIMULASI_TKA']);
    }

    /** @test */
    public function admin_manages_registry_while_guru_owns_tp_atp_authoring_permission(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Role::query()->where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $guru = Role::query()->where('name', 'GURU')->firstOrFail();

        $this->assertTrue($admin->permissions()->where('name', 'curriculum.view')->exists());
        $this->assertTrue($admin->permissions()->where('name', 'curriculum.registry.manage')->exists());
        $this->assertTrue($admin->permissions()->where('name', 'curriculum.source.manage')->exists());
        $this->assertTrue($admin->permissions()->where('name', 'assessment.taxonomy.manage')->exists());
        $this->assertFalse($admin->permissions()->where('name', 'curriculum.manage')->exists());

        $this->assertTrue($guru->permissions()->where('name', 'curriculum.view')->exists());
        $this->assertTrue($guru->permissions()->where('name', 'curriculum.manage')->exists());
        $this->assertFalse($guru->permissions()->where('name', 'curriculum.registry.manage')->exists());
        $this->assertFalse($guru->permissions()->where('name', 'curriculum.source.manage')->exists());
    }
}
