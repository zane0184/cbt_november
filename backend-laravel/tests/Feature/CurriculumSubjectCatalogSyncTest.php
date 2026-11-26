<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\CurriculumRegistryManagement;
use App\Models\AuditLog;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantCurriculumSubjectMapping;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurriculumSubjectCatalogSyncTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private MultiCurriculumVersion $version;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'MIN Subject Sync Test',
            'code' => 'MINSUBJECTSYNC',
        ]);
        app(TenantContext::class)->setTenantId((string) $this->tenant->id);

        $role = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $permission = Permission::firstOrCreate([
            'name' => 'curriculum.registry.manage',
        ], [
            'label' => 'Kelola Registry Kurikulum',
        ]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Subject Sync',
            'username' => 'admin.subject.sync',
            'password' => bcrypt('StrongTestPassword123!'),
        ]);
        $this->admin->roles()->sync([$role->id]);
        $this->actingAs($this->admin);

        $this->version = MultiCurriculumVersion::query()
            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
            ->where('version_code', 'KMA-1503-2025')
            ->where('status', 'ACTIVE')
            ->firstOrFail();
    }

    public function test_official_subject_catalog_is_synced_to_local_tenant_idempotently_and_preserves_local_edits_and_history(): void
    {
        $nodes = MultiCurriculumStructure::query()
            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
            ->where('multi_curriculum_version_id', $this->version->id)
            ->where('node_type', 'SUBJECT')
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->where('is_editable', false)
            ->get();

        $this->assertGreaterThan(0, $nodes->count());

        // Existing manually-created Mapel must be reused even when its local code differs.
        $existingMath = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'short_name' => 'MTK',
            'status' => 'ACTIVE',
        ]);

        $component = Livewire::test(CurriculumRegistryManagement::class)
            ->call('registerVersion', (string) $this->version->id)
            ->assertHasNoErrors();

        $registration = TenantCurriculumRegistration::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('multi_curriculum_version_id', $this->version->id)
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        $component->call('syncOfficialSubjects')
            ->assertHasNoErrors()
            ->assertSee('Sinkronisasi katalog resmi selesai');

        $expectedUniqueCodes = $nodes
            ->map(fn (MultiCurriculumStructure $node) => strtoupper((string) $node->metadata['subject_code']))
            ->unique()
            ->count();

        $subjects = Subject::query()->where('tenant_id', $this->tenant->id)->get();
        $mappings = TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('tenant_curriculum_registration_id', $registration->id)
            ->get();

        $this->assertSame($expectedUniqueCodes, $subjects->count());
        $this->assertSame($nodes->count(), $mappings->count());
        $this->assertDatabaseHas('subjects', [
            'tenant_id' => $this->tenant->id,
            'id' => $existingMath->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseMissing('subjects', [
            'tenant_id' => $this->tenant->id,
            'code' => 'MATEMATIKA',
        ]);
        $mathMapping = $mappings->firstWhere('subject_id', $existingMath->id);
        $this->assertNotNull($mathMapping);
        $this->assertSame('MATEMATIKA', $mathMapping->metadata['official_subject_code']);
        $this->assertDatabaseHas('subjects', [
            'tenant_id' => $this->tenant->id,
            'code' => 'SKI',
            'name' => 'Sejarah Kebudayaan Islam',
            'status' => 'ACTIVE',
        ]);

        $math = Subject::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('code', 'MAT')
            ->firstOrFail();
        $math->update(['name' => 'Matematika Lokal']);

        $retiredMapping = $mappings->firstOrFail();
        $retiredMapping->update(['status' => 'INACTIVE']);

        $component->call('syncOfficialSubjects')->assertHasNoErrors();

        $this->assertSame($expectedUniqueCodes, Subject::query()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame($nodes->count(), TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('tenant_curriculum_registration_id', $registration->id)
            ->count());
        $this->assertDatabaseHas('subjects', [
            'id' => $math->id,
            'tenant_id' => $this->tenant->id,
            'name' => 'Matematika Lokal',
        ]);
        $this->assertDatabaseHas('tenant_curriculum_subject_mappings', [
            'id' => $retiredMapping->id,
            'tenant_id' => $this->tenant->id,
            'status' => 'INACTIVE',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CURRICULUM_SUBJECT_CATALOG_SYNCED',
            'entity_id' => $registration->id,
        ]);
    }

    public function test_sync_does_not_create_subjects_for_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'MIN Other Subject Sync',
            'code' => 'MINOTHERSUBJECT',
        ]);

        Livewire::test(CurriculumRegistryManagement::class)
            ->call('registerVersion', (string) $this->version->id)
            ->call('syncOfficialSubjects')
            ->assertHasNoErrors();

        $this->assertGreaterThan(
            0,
            Subject::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count()
        );
        $this->assertSame(
            0,
            Subject::withoutGlobalScopes()->where('tenant_id', $otherTenant->id)->count()
        );
    }


    public function test_sync_fails_closed_when_the_ski_alias_is_already_used_for_an_unrelated_local_subject(): void
    {
        Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'SKI',
            'name' => 'Sejarah Indonesia',
            'short_name' => 'Sejarah',
            'status' => 'ACTIVE',
        ]);

        $component = Livewire::test(CurriculumRegistryManagement::class)
            ->call('registerVersion', (string) $this->version->id)
            ->assertHasNoErrors();

        $component->call('syncOfficialSubjects')
            ->assertHasErrors(['selectedRegistrationId']);

        $this->assertSame(1, Subject::query()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(0, TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->count());
    }

    public function test_non_admin_cannot_sync_official_subject_catalog(): void
    {
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Subject Sync',
            'username' => 'guru.subject.sync',
            'password' => bcrypt('StrongTestPassword123!'),
        ]);
        $guru->roles()->sync([$guruRole->id]);
        $this->actingAs($guru);

        Livewire::test(CurriculumRegistryManagement::class)->assertForbidden();
    }
}
