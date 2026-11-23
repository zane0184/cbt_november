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

        $this->assertTrue(Schema::hasColumn('multi_curriculum_versions', 'source_id'));
        $this->assertTrue(Schema::hasColumn('multi_curriculum_structures', 'verification_status'));
        $this->assertTrue(Schema::hasColumn('multi_learning_outcomes', 'source_checksum'));
        $this->assertTrue(Schema::hasColumn('assessments', 'assessment_function_option_id'));
        $this->assertTrue(Schema::hasColumn('assessments', 'curriculum_context_snapshot'));
        $this->assertTrue(Schema::hasColumn('exams', 'assessment_context_snapshot'));
        $this->assertTrue(Schema::hasColumn('exam_question_snapshots', 'curriculum_context_snapshot'));
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

        $religionCp = CurriculumReferenceSource::query()
            ->where('document_number', 'Kepdirjen Pendis 9941 Tahun 2025')
            ->firstOrFail();

        $this->assertSame('PENDING', $religionCp->verification_status);
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
