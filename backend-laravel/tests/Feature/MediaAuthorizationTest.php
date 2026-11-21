<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\MediaAsset;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $teacher;
    private User $otherTeacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Tenant Media', 'code' => 'TM']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $role = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Pemilik',
            'email' => 'pemilik@media.test',
            'password' => bcrypt('password'),
        ]);
        $this->teacher->roles()->attach($role);

        $this->otherTeacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Lain',
            'email' => 'lain@media.test',
            'password' => bcrypt('password'),
        ]);
        $this->otherTeacher->roles()->attach($role);
        $this->bootstrapCbtRolePermissions();
    }

    public function test_admin_multi_role_cannot_access_question_media_library(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Multi Role',
            'email' => 'admin-multi@media.test',
            'password' => bcrypt('password'),
        ]);
        $admin->roles()->attach([$adminRole->id, $guruRole->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/media')
            ->assertForbidden();
    }

    public function test_user_media_owner_is_forced_to_authenticated_user(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/v1/media', [
                'file' => UploadedFile::fake()->create('private.pdf', 20, 'application/pdf'),
                'owner_type' => 'USER',
                'owner_id' => $this->otherTeacher->id,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_user_media_is_private_to_its_owner(): void
    {
        Storage::fake('local');
        $path = 'private/tenants/' . $this->tenant->id . '/media/private.pdf';
        Storage::disk('local')->put($path, 'private test content');

        $media = MediaAsset::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'USER',
            'owner_id' => $this->teacher->id,
            'file_name' => 'private.pdf',
            'original_name' => 'private.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 20,
            'disk' => 'local',
            'uploaded_by' => $this->teacher->id,
        ]);

        $this->actingAs($this->otherTeacher, 'sanctum')
            ->getJson("/api/v1/media/{$media->id}")
            ->assertForbidden();

        $this->actingAs($this->teacher, 'sanctum')
            ->get("/api/v1/media/{$media->id}")
            ->assertOk();
    }

    public function test_media_resource_has_no_unimplemented_update_route(): void
    {
        $routes = app('router')->getRoutes();
        $mediaUpdateRoutes = collect($routes)->filter(function ($route) {
            return str_contains($route->uri(), 'api/v1/media')
                && in_array('PUT', $route->methods(), true);
        });

        $this->assertCount(0, $mediaUpdateRoutes);
    }

    public function test_teacher_can_attach_own_question_media_to_draft_version(): void
    {
        [$question, $version] = $this->createDraftQuestion();
        $media = $this->createQuestionMedia($question);

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/v1/questions/{$question->id}/versions/{$version->id}/media", [
                'media_id' => $media->id,
                'role' => 'STEM',
                'sequence' => 0,
            ])
            ->assertCreated()
            ->assertJsonPath('data.media_id', $media->id);

        $this->assertDatabaseHas('question_version_media', [
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $version->id,
            'media_asset_id' => $media->id,
            'role' => 'STEM',
        ]);
    }

    public function test_question_media_cannot_be_attached_to_another_question(): void
    {
        [$question, $version] = $this->createDraftQuestion();
        [$otherQuestion] = $this->createDraftQuestion('Soal lain');
        $media = $this->createQuestionMedia($otherQuestion);

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/v1/questions/{$question->id}/versions/{$version->id}/media", [
                'media_id' => $media->id,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('question_version_media', [
            'question_version_id' => $version->id,
            'media_asset_id' => $media->id,
        ]);
    }

    public function test_question_media_from_another_tenant_cannot_be_attached(): void
    {
        $otherTenant = Tenant::create(['name' => 'Tenant Other Media', 'code' => 'TOM']);
        app(TenantContext::class)->setTenantId($otherTenant->id);
        $otherTeacher = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Guru Tenant Lain',
            'email' => 'tenant-lain@media.test',
            'password' => bcrypt('password'),
        ]);
        $role = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $otherTeacher->roles()->attach($role);
        $foreignQuestion = Question::create([
            'owner_type' => 'PERSONAL',
            'owner_user_id' => $otherTeacher->id,
            'status' => 'DRAFT',
            'visibility' => 'PRIVATE',
            'created_by' => $otherTeacher->id,
        ]);
        $foreignVersion = QuestionVersion::create([
            'question_id' => $foreignQuestion->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'ESSAY',
            'stem' => 'Foreign tenant question',
            'status' => 'DRAFT',
            'is_current' => true,
            'created_by' => $otherTeacher->id,
        ]);
        $foreignMedia = MediaAsset::create([
            'tenant_id' => $otherTenant->id,
            'owner_type' => 'QUESTION',
            'owner_id' => $foreignQuestion->id,
            'file_name' => 'foreign.pdf',
            'original_name' => 'foreign.pdf',
            'storage_path' => "private/tenants/{$otherTenant->id}/media/foreign.pdf",
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 10,
            'disk' => 'local',
            'uploaded_by' => $otherTeacher->id,
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);
        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/v1/questions/{$foreignQuestion->id}/versions/{$foreignVersion->id}/media", [
                'media_id' => $foreignMedia->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('question_version_media', [
            'question_version_id' => $foreignVersion->id,
            'media_asset_id' => $foreignMedia->id,
        ]);
    }

    public function test_published_question_version_media_is_immutable(): void
    {
        [$question, $version] = $this->createDraftQuestion();
        $version->update(['status' => 'PUBLISHED']);
        $media = $this->createQuestionMedia($question);

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/v1/questions/{$question->id}/versions/{$version->id}/media", [
                'media_id' => $media->id,
            ])
            ->assertConflict();

        $this->assertDatabaseMissing('question_version_media', [
            'question_version_id' => $version->id,
            'media_asset_id' => $media->id,
        ]);
    }

    public function test_soft_deleted_question_media_cannot_be_attached(): void
    {
        [$question, $version] = $this->createDraftQuestion();
        $media = $this->createQuestionMedia($question);
        $media->delete();

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/v1/questions/{$question->id}/versions/{$version->id}/media", [
                'media_id' => $media->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('question_version_media', [
            'question_version_id' => $version->id,
            'media_asset_id' => $media->id,
        ]);
    }

    public function test_attaching_same_media_twice_is_rejected_and_detach_is_audited(): void
    {
        [$question, $version] = $this->createDraftQuestion();
        $media = $this->createQuestionMedia($question);
        $url = "/api/v1/questions/{$question->id}/versions/{$version->id}/media";

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson($url, ['media_id' => $media->id])
            ->assertCreated();

        $this->actingAs($this->teacher, 'sanctum')
            ->postJson($url, ['media_id' => $media->id])
            ->assertConflict();

        $this->actingAs($this->teacher, 'sanctum')
            ->deleteJson("{$url}/{$media->id}")
            ->assertOk();

        $this->assertDatabaseMissing('question_version_media', [
            'question_version_id' => $version->id,
            'media_asset_id' => $media->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'DETACH_QUESTION_VERSION_MEDIA',
            'entity_id' => $version->id,
        ]);
    }

    private function createDraftQuestion(string $stem = 'Pertanyaan media'): array
    {
        $question = Question::create([
            'owner_type' => 'PERSONAL',
            'owner_user_id' => $this->teacher->id,
            'status' => 'DRAFT',
            'visibility' => 'PRIVATE',
            'created_by' => $this->teacher->id,
        ]);

        $version = QuestionVersion::create([
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => $stem,
            'difficulty' => 'MEDIUM',
            'default_points' => 1,
            'status' => 'DRAFT',
            'is_current' => true,
            'created_by' => $this->teacher->id,
        ]);

        return [$question, $version];
    }

    private function createQuestionMedia(Question $question): MediaAsset
    {
        return MediaAsset::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'QUESTION',
            'owner_id' => $question->id,
            'file_name' => 'question-' . $question->id . '.pdf',
            'original_name' => 'question.pdf',
            'storage_path' => 'private/question-' . $question->id . '.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 20,
            'disk' => 'local',
            'uploaded_by' => $this->teacher->id,
        ]);
    }

    public function test_teacher_media_index_only_lists_own_uploads(): void
    {
        $own = MediaAsset::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'USER',
            'owner_id' => $this->teacher->id,
            'file_name' => 'own.pdf',
            'original_name' => 'own.pdf',
            'storage_path' => 'private/own.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 10,
            'disk' => 'local',
            'uploaded_by' => $this->teacher->id,
        ]);

        MediaAsset::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'USER',
            'owner_id' => $this->otherTeacher->id,
            'file_name' => 'other.pdf',
            'original_name' => 'other.pdf',
            'storage_path' => 'private/other.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 10,
            'disk' => 'local',
            'uploaded_by' => $this->otherTeacher->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/v1/media');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $own->id]);
        $response->assertJsonMissing(['original_name' => 'other.pdf']);
        $response->assertJsonMissing(['storage_path' => 'private/own.pdf']);
    }
}
