<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\CurriculumReferenceSource;
use App\Models\GradeLevel;
use App\Models\LearningOutcomeStructureMapping;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiLearningOutcome;
use App\Models\Phase;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\TenantLearningOutcome;
use App\Models\User;
use App\Services\TeacherPortal\TeacherLearningOutcomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\CreatesCanonicalCurriculumContext;
use Tests\TestCase;

class TeacherLearningOutcomeProvenanceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalCurriculumContext;

    private Tenant $tenant;
    private User $teacher;
    private TeacherSubjectAssignment $assignment;
    private array $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'P0-02A.1 Provenance Test', 'code' => 'P002A1']);
        $this->setTenantContext($this->tenant->id);
        $this->bootstrapCbtRolePermissions();

        $teacherRole = Role::query()->where('name', 'GURU')->firstOrFail();
        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Provenance Test',
            'email' => 'guru.provenance@example.test',
            'password' => bcrypt('Acceptance-Password-2026'),
        ]);
        $this->teacher->roles()->sync([$teacherRole->id]);

        $profile = TeacherProfile::create($this->withSyntheticTeacherIdentity([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->teacher->id,
            'full_name' => 'Guru Provenance Test',
        ]));

        $curriculum = Curriculum::create([
            'name' => 'Kurikulum Merdeka',
            'code' => 'K-MERDEKA',
            'is_active' => true,
        ]);
        $phase = Phase::create([
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase C',
            'code' => 'C',
        ]);
        $academicYear = AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $semester = Semester::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $gradeLevel = GradeLevel::create([
            'phase_id' => $phase->id,
            'name' => 'Kelas 6',
            'code' => '6',
            'level' => 6,
        ]);
        $subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'FIKIH',
            'name' => 'Fikih',
            'short_name' => 'FIQ',
            'status' => 'ACTIVE',
        ]);
        $rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 6-A',
            'status' => 'ACTIVE',
        ]);

        $this->assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $profile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addYears(5)->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->teacher->id,
        ]);

        $this->context = $this->createCanonicalCurriculumContext($this->assignment, $this->teacher);
        $this->actingAs($this->teacher);
    }

    /** @test */
    public function atp_creation_rejects_pending_cp_without_partial_rows(): void
    {
        $this->context['cp']->source->update(['verification_status' => 'PENDING']);
        $this->assertAtpRejectedWithoutPartialRows('CP_SOURCE_UNVERIFIED');
    }

    /** @test */
    public function atp_creation_rejects_source_without_checksum_without_partial_rows(): void
    {
        $this->context['cp']->source->update(['checksum_sha256' => null]);
        $this->assertAtpRejectedWithoutPartialRows('CP_CHECKSUM_MISMATCH');
    }

    /** @test */
    public function atp_creation_rejects_mismatched_source_checksum_without_partial_rows(): void
    {
        $this->context['cp']->source->update(['checksum_sha256' => str_repeat('b', 64)]);
        $this->assertAtpRejectedWithoutPartialRows('CP_CHECKSUM_MISMATCH');
    }

    /** @test */
    public function atp_creation_rejects_mismatched_cp_checksum_without_partial_rows(): void
    {
        $this->context['cp']->update(['source_checksum' => str_repeat('c', 64)]);
        $this->assertAtpRejectedWithoutPartialRows('CP_CHECKSUM_MISMATCH');
    }

    /** @test */
    public function atp_creation_reloads_previously_verified_cp_after_status_or_source_provenance_changes(): void
    {
        $this->assertSame('ARTIFACT_CHECKSUM_VERIFIED', $this->context['cp']->verification_status);
        $this->context['cp']->update(['status' => 'INACTIVE']);
        $this->assertAtpRejectedWithoutPartialRows('CP_NOT_ACTIVE');

        $this->context['cp']->update(['status' => 'ACTIVE']);
        $this->context['cp']->source->update(['verification_status' => 'PENDING']);
        $this->assertAtpRejectedWithoutPartialRows('CP_SOURCE_UNVERIFIED');
    }

    /** @test */
    public function atp_creation_rejects_tp_linked_to_a_cp_from_another_subject_structure(): void
    {
        $otherSubjectStructure = MultiCurriculumStructure::query()
            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
            ->where('multi_curriculum_version_id', $this->context['version']->id)
            ->where('node_type', 'SUBJECT')
            ->where('id', '!=', $this->context['subject_structure']->id)
            ->firstOrFail();

        $source = $this->context['cp']->source;
        $otherCp = MultiLearningOutcome::create([
            'tenant_id' => Tenant::SYSTEM_TENANT_ID,
            'multi_curriculum_structure_id' => $otherSubjectStructure->id,
            'multi_curriculum_version_id' => $this->context['version']->id,
            'code' => 'TEST-CP-WRONG-SUBJECT-STRUCTURE',
            'outcome_type' => 'CP',
            'description' => 'Test-only CP row for wrong-structure relation rejection.',
            'status' => 'ACTIVE',
            'source_id' => $source->id,
            'source_locator' => 'P0-02A.1 wrong-structure test fixture',
            'verification_status' => 'ARTIFACT_CHECKSUM_VERIFIED',
            'source_checksum' => $source->checksum_sha256,
            'is_official' => true,
            'is_editable' => false,
            'metadata' => ['test_fixture_only' => true, 'not_official_cp_content' => true],
        ]);

        // Composite owner/version FK remains valid, but the CP does not belong
        // to the subject structure pinned by this teacher assignment.
        $this->context['tp']->update(['parent_cp_id' => $otherCp->id]);
        $this->assertAtpRejectedWithoutPartialRows('CP_PARENT_RELATION_INVALID');
    }

    /** @test */
    public function atp_creation_succeeds_for_checksum_consistent_test_provenance_fixture(): void
    {
        // This synthetic CP exists only to exercise the positive code path.
        // It is not an official CP artifact and is not evidence of official CP availability.
        $cp = $this->context['cp']->fresh('source');
        $this->assertSame('ARTIFACT_CHECKSUM_VERIFIED', $cp->verification_status);
        $this->assertSame('ARTIFACT_CHECKSUM_VERIFIED', $cp->source->verification_status);
        $this->assertSame($cp->source->checksum_sha256, $cp->source_checksum);

        $atp = app(TeacherLearningOutcomeService::class)->createAtp(
            $this->assignment->id,
            $this->context['tp']->id,
            'ATP-PROVENANCE-PASS',
            'Alur tujuan pembelajaran provenance test',
            'Deskripsi uji positif untuk ATP dengan provenance fixture konsisten.',
            1
        );

        $this->assertSame('ATP', $atp->outcome_type);
        $this->assertSame($cp->id, $atp->parent_cp_id);
        $this->assertSame($this->context['tp']->id, $atp->parent_tp_id);
        $this->assertDatabaseHas('tenant_learning_outcomes', [
            'id' => $atp->id,
            'outcome_type' => 'ATP',
            'status' => 'DRAFT',
        ]);
        $atpMappings = LearningOutcomeStructureMapping::query()
            ->where('tenant_learning_outcome_id', $atp->id)
            ->get();
        $this->assertGreaterThan(0, $atpMappings->count());
        foreach ($atpMappings as $mapping) {
            $this->assertSame((string) $this->assignment->id, (string) $mapping->teacher_subject_assignment_id);
            $this->assertSame((string) $this->context['registration']->id, (string) $mapping->tenant_curriculum_registration_id);
            $this->assertSame((string) $this->context['version']->id, (string) $mapping->multi_curriculum_version_id);
            $this->assertSame(Tenant::SYSTEM_TENANT_ID, $mapping->curriculum_owner_id);
        }
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'TEACHER_LEARNING_OUTCOME_CREATED',
            'entity_id' => $atp->id,
        ]);
    }

    /** @test */
    public function tp_service_persists_assignment_scoped_framework_mappings_and_allows_atp_creation(): void
    {
        // The CP is a synthetic, checksum-consistent PHPUnit fixture only.
        // This test exercises the TP -> ATP service path, not official CP availability.
        $pmOlah = MultiCurriculumStructure::query()
            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
            ->where('multi_curriculum_version_id', $this->context['version']->id)
            ->where('node_type', 'PM_OLAH')
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->where('is_editable', false)
            ->firstOrFail();

        $tp = app(TeacherLearningOutcomeService::class)->createTp(
            $this->assignment->id,
            $this->context['cp']->id,
            'TP-SERVICE-MAPPING-CHAIN',
            'TP dibuat melalui service',
            'Regression test untuk mapping TP yang terikat pada assignment.',
            [$this->context['framework']['PANCA_CINTA']->id],
            [$this->context['framework']['PM_PRINCIPLE']->id],
            [$pmOlah->id],
            [],
            [$this->context['framework']['DIMENSI_PROFIL_LULUSAN']->id]
        );

        $tpMappings = LearningOutcomeStructureMapping::query()
            ->where('tenant_learning_outcome_id', $tp->id)
            ->get();

        $this->assertCount(4, $tpMappings);
        foreach ($tpMappings as $mapping) {
            $this->assertSame($this->assignment->id, $mapping->teacher_subject_assignment_id);
        }

        $atp = app(TeacherLearningOutcomeService::class)->createAtp(
            $this->assignment->id,
            $tp->id,
            'ATP-SERVICE-MAPPING-CHAIN',
            'ATP dibuat melalui service',
            'Regression test untuk pewarisan mapping TP ke ATP.',
            1
        );

        $this->assertSame($tp->id, $atp->parent_tp_id);
        $this->assertSame($this->context['cp']->id, $atp->parent_cp_id);
        $this->assertSame($tpMappings->count(), LearningOutcomeStructureMapping::query()
            ->where('tenant_learning_outcome_id', $atp->id)
            ->count());
        $this->assertSame(4, LearningOutcomeStructureMapping::query()
            ->where('tenant_learning_outcome_id', $atp->id)
            ->where('teacher_subject_assignment_id', $this->assignment->id)
            ->count());
    }

    /** @test */
    public function create_atp_and_publish_require_curriculum_manage_permission_when_service_is_called_directly(): void
    {
        $role = Role::query()->where('name', 'GURU')->firstOrFail();
        $permission = Permission::query()->where('name', 'curriculum.manage')->firstOrFail();
        $role->permissions()->detach($permission->id);
        $this->teacher->unsetRelation('roles');
        $this->actingAs($this->teacher);

        try {
            app(TeacherLearningOutcomeService::class)->createTp(
                $this->assignment->id,
                $this->context['cp']->id,
                'TP-NO-PERMISSION',
                'TP tanpa permission',
                'Deskripsi uji penolakan permission langsung di service.',
                [],
                [],
                [],
                [],
                []
            );
            $this->fail('Direct createTp service call must enforce curriculum.manage.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertStringStartsWith('CURRICULUM_PERMISSION_DENIED', $exception->getMessage());
        }

        try {
            app(TeacherLearningOutcomeService::class)->createAtp(
                $this->assignment->id,
                $this->context['tp']->id,
                'ATP-NO-PERMISSION',
                'ATP tanpa permission',
                'Deskripsi uji penolakan permission langsung di service.',
                1
            );
            $this->fail('Direct service call must enforce curriculum.manage.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertStringStartsWith('CURRICULUM_PERMISSION_DENIED', $exception->getMessage());
        }

        try {
            app(TeacherLearningOutcomeService::class)->publish($this->context['tp']->id);
            $this->fail('Direct publish service call must enforce curriculum.manage.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertStringStartsWith('CURRICULUM_PERMISSION_DENIED', $exception->getMessage());
        }

        $this->assertSame(0, TenantLearningOutcome::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('outcome_type', 'ATP')
            ->count());
    }

    /** @test */
    public function atp_mapping_insert_failure_rolls_back_atp_mappings_and_audit_log(): void
    {
        $beforeAtp = TenantLearningOutcome::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('outcome_type', 'ATP')
            ->count();
        $beforeMappings = LearningOutcomeStructureMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->count();
        $beforeAudit = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('tenant_id', $this->tenant->id)
            ->count();

        $eventName = 'eloquent.creating: ' . LearningOutcomeStructureMapping::class;
        $mappingInserts = 0;
        Event::listen($eventName, function (LearningOutcomeStructureMapping $mapping) use (&$mappingInserts): void {
            if (isset($mapping->metadata['inherited_from_tp_id'])) {
                $mappingInserts++;
                if ($mappingInserts === 2) {
                    throw new RuntimeException('P0-02A.1 injected second ATP mapping insert failure.');
                }
            }
        });

        try {
            app(TeacherLearningOutcomeService::class)->createAtp(
                $this->assignment->id,
                $this->context['tp']->id,
                'ATP-ROLLBACK-TEST',
                'ATP rollback test',
                'Uji rollback ketika insert mapping kedua gagal.',
                1
            );
            $this->fail('Injected mapping failure must abort ATP creation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('P0-02A.1 injected second ATP mapping insert failure.', $exception->getMessage());
        } finally {
            Event::forget($eventName);
        }

        $this->assertSame(2, $mappingInserts);
        $this->assertSame($beforeAtp, TenantLearningOutcome::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('outcome_type', 'ATP')
            ->count());
        $this->assertSame($beforeMappings, LearningOutcomeStructureMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->count());
        $this->assertSame($beforeAudit, \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('tenant_id', $this->tenant->id)
            ->count());
    }

    /** @test */
    public function publish_rechecks_cp_provenance_after_tp_was_created(): void
    {
        $this->context['tp']->update(['status' => 'DRAFT']);
        $this->context['cp']->update(['status' => 'INACTIVE']);
        $beforeAudit = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('tenant_id', $this->tenant->id)
            ->count();

        try {
            app(TeacherLearningOutcomeService::class)->publish($this->context['tp']->id);
            $this->fail('Publishing a TP must recheck its CP provenance.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors()['outcome'] ?? [];
            $this->assertNotEmpty($errors);
            $this->assertSame('CP_NOT_ACTIVE', explode(':', $errors[0], 2)[0]);
        }

        $this->assertSame('DRAFT', $this->context['tp']->fresh()->status);
        $this->assertSame($beforeAudit, \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('tenant_id', $this->tenant->id)
            ->count());
    }

    private function assertAtpRejectedWithoutPartialRows(string $expectedCode): void
    {
        $beforeAtp = TenantLearningOutcome::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('outcome_type', 'ATP')
            ->count();
        $beforeMappings = LearningOutcomeStructureMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->count();
        $beforeAudit = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('tenant_id', $this->tenant->id)
            ->count();

        try {
            app(TeacherLearningOutcomeService::class)->createAtp(
                $this->assignment->id,
                $this->context['tp']->id,
                'ATP-REJECTED-' . strtoupper(substr((string) $this->context['tp']->id, 0, 8)),
                'ATP yang wajib ditolak',
                'Deskripsi uji penolakan provenance dengan tanpa data parsial.',
                1
            );
            $this->fail('ATP creation must reject an invalid parent CP provenance.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors()['parentTpId'] ?? [];
            $this->assertNotEmpty($errors);
            $this->assertSame($expectedCode, explode(':', $errors[0], 2)[0]);
        }

        $this->assertSame($beforeAtp, TenantLearningOutcome::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('outcome_type', 'ATP')
            ->count());
        $this->assertSame($beforeMappings, LearningOutcomeStructureMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->count());
        $this->assertSame($beforeAudit, \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('tenant_id', $this->tenant->id)
            ->count());
    }
}
