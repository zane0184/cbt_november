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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaOrphanCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Tenant Cleanup', 'code' => 'TC']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Cleanup',
            'email' => 'cleanup@media.test',
            'password' => bcrypt('password'),
        ]);
        $role = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->teacher->roles()->attach($role);
        Storage::fake('local');
    }

    public function test_cleanup_removes_old_unreferenced_file_and_soft_deleted_row(): void
    {
        $path = "private/tenants/{$this->tenant->id}/media/orphan.pdf";
        Storage::disk('local')->put($path, 'orphan');
        $media = $this->createMedia($path);
        $media->delete();
        $media->deleted_at = now()->subDays(2);
        $media->saveQuietly();

        $this->artisan('cbt:cleanup-orphaned-media', ['--age-hours' => 24, '--limit' => 50])
            ->assertExitCode(0);

        $this->assertNull(MediaAsset::withoutGlobalScopes()->whereKey($media->id)->first());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_cleanup_does_not_delete_storage_outside_tenant_media_directory(): void
    {
        $path = 'private/shared/keep-me.pdf';
        Storage::disk('local')->put($path, 'do not delete');
        $media = $this->createMedia($path);
        $media->delete();
        $media->deleted_at = now()->subDays(2);
        $media->saveQuietly();

        $this->artisan('cbt:cleanup-orphaned-media', ['--age-hours' => 24, '--limit' => 50])
            ->assertExitCode(0);

        $this->assertNotNull(MediaAsset::withoutGlobalScopes()->whereKey($media->id)->first());
        Storage::disk('local')->assertExists($path);
    }

    public function test_cleanup_rejects_traversal_path_even_when_it_has_tenant_prefix(): void
    {
        $outsidePath = 'private/shared/keep-me.pdf';
        Storage::disk('local')->put($outsidePath, 'do not delete');
        $traversalPath = "private/tenants/{$this->tenant->id}/media/../../shared/keep-me.pdf";
        $media = $this->createMedia($traversalPath);
        $media->delete();
        $media->deleted_at = now()->subDays(2);
        $media->saveQuietly();

        $this->artisan('cbt:cleanup-orphaned-media', ['--age-hours' => 24, '--limit' => 50])
            ->assertExitCode(0);

        $this->assertNotNull(MediaAsset::withoutGlobalScopes()->whereKey($media->id)->first());
        Storage::disk('local')->assertExists($outsidePath);
    }

    public function test_cleanup_rejects_non_local_disk_even_with_tenant_path(): void
    {
        $path = "private/tenants/{$this->tenant->id}/media/remote.pdf";
        $media = $this->createMedia($path);
        $media->disk = 'public';
        $media->delete();
        $media->deleted_at = now()->subDays(2);
        $media->saveQuietly();

        $this->artisan('cbt:cleanup-orphaned-media', ['--age-hours' => 24, '--limit' => 50])
            ->assertExitCode(0);

        $this->assertNotNull(MediaAsset::withoutGlobalScopes()->whereKey($media->id)->first());
    }

    public function test_cleanup_preserves_media_still_referenced_by_question_version(): void
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
            'question_type' => 'ESSAY',
            'stem' => 'Question with media',
            'status' => 'DRAFT',
            'is_current' => true,
            'created_by' => $this->teacher->id,
        ]);
        $path = "private/tenants/{$this->tenant->id}/media/referenced.pdf";
        Storage::disk('local')->put($path, 'referenced');
        $media = $this->createMedia($path);
        $version->media()->attach($media->id, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'role' => 'STEM',
            'sequence' => 0,
        ]);
        $media->delete();
        $media->deleted_at = now()->subDays(2);
        $media->saveQuietly();

        $this->artisan('cbt:cleanup-orphaned-media', ['--age-hours' => 24, '--limit' => 50])
            ->assertExitCode(0);

        $this->assertNotNull(MediaAsset::withoutGlobalScopes()->whereKey($media->id)->first());
        Storage::disk('local')->assertExists($path);
    }

    private function createMedia(string $path): MediaAsset
    {
        return MediaAsset::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'USER',
            'owner_id' => $this->teacher->id,
            'file_name' => basename($path),
            'original_name' => basename($path),
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 10,
            'disk' => 'local',
            'uploaded_by' => $this->teacher->id,
        ]);
    }
}
