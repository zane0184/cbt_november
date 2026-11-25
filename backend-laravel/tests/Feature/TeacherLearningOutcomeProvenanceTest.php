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
use Illuminate\Validation\ValidationException;
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
        $this->assertAtpRejectedWithoutPartialRows();
    }

    /** @test */
    public function atp_creation_rejects_source_without_checksum_without_partial_rows(): void
    {
        $this->context['cp']->source->update(['checksum_sha256' => null]);
        $this->assertAtpRejectedWithoutPartialRows();
    }

    /** @test */
    public function atp_creation_rejects_mismatched_source_checksum_without_partial_rows(): void
    {
        $this->context['cp']->source->update(['checksum_sha256' => str_repeat('b', 64)]);
        $this->assertAtpRejectedWithoutPartialRows();
    }

    /** @test */
    public function atp_creation_rejects_mismatched_cp_checksum_without_partial_rows(): void
    {
        $this->context['cp']->update(['source_checksum' => str_repeat('c', 64)]);
        $this->assertAtpRejectedWithoutPartialRows();
    }

    /** @test */
    public function atp_creation_reloads_previously_verified_cp_after_status_or_source_provenance_changes(): void
    {
        $this->assertSame('ARTIFACT_CHECKSUM_VERIFIED', $this->context['cp']->verification_status);
        $this->context['cp']->update(['status' => 'INACTIVE']);
        $this->assertAtpRejectedWithoutPartialRows();

        $this->context['cp']->update(['status' => 'ACTIVE']);
        $this->context['cp']->source->update(['verification_status' => 'PENDING']);
        $this->assertAtpRejectedWithoutPartialRows();
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
        $this->assertAtpRejectedWithoutPartialRows();
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
        $this->assertGreaterThan(0, LearningOutcomeStructureMapping::query()
            ->where('tenant_learning_outcome_id', $atp->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'TEACHER_LEARNING_OUTCOME_CREATED',
            'entity_id' => $atp->id,
        ]);
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
        }

        try {
            app(TeacherLearningOutcomeService::class)->publish($this->context['tp']->id);
            $this->fail('Direct publish service call must enforce curriculum.manage.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, TenantLearningOutcome::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('outcome_type', 'ATP')
            ->count());
    }

    private function assertAtpRejectedWithoutPartialRows(): void
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
            $this->assertNotEmpty($exception->errors()['parentTpId'] ?? []);
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
