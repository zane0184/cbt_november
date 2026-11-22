<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\MediaAsset;
use App\Livewire\Admin\QuestionBank;
use App\Models\Permission;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

final class TeacherQuestionMediaTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;

    private Tenant $tenant;
    private User $teacher;
    private Question $question;
    private QuestionVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Madrasah Media Test',
            'code' => 'MMT01',
            'status' => 'ACTIVE',
        ]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $role = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $permissions = collect(['question.view', 'question.manage'])
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name], ['label' => $name]));
        $role->permissions()->sync($permissions->pluck('id')->all());

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Media',
            'email' => 'guru.media@test.local',
            'password' => bcrypt('password123'),
        ]);
        $this->teacher->roles()->attach($role->id);

        $scope = $this->createTeacherExamScope($this->tenant, $this->teacher, [], 'MEDIA');
        $this->question = Question::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'SUBJECT',
            'question_code' => 'MEDIA-001',
            'status' => 'DRAFT',
            'subject_id' => $scope['subject']->id,
            'created_by' => $this->teacher->id,
            'updated_by' => $this->teacher->id,
        ]);
        $this->version = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $this->question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Soal dengan media',
            'explanation' => '',
            'default_points' => 10,
            'status' => 'DRAFT',
            'is_current' => true,
            'created_by' => $this->teacher->id,
        ]);
    }

    public function test_guru_can_upload_attach_view_and_detach_private_question_media(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->teacher)->post(
            route('teacher.questions.media.store', [
                'question' => $this->question->id,
                'version' => $this->version->id,
            ]),
            [
                'file' => UploadedFile::fake()->image('diagram.png'),
                'role' => 'STEM',
                'sequence' => 0,
            ]
        );

        $response->assertRedirect(route('teacher.questions.index', ['edit_question' => $this->question->id]));

        $media = MediaAsset::query()->where('owner_id', $this->question->id)->firstOrFail();
        $this->assertSame('QUESTION', $media->owner_type);
        $this->assertSame($this->tenant->id, $media->tenant_id);
        $this->assertSame($this->teacher->id, $media->uploaded_by);
        $this->assertTrue(Storage::disk($media->disk)->exists($media->storage_path));
        $this->assertTrue($this->version->fresh()->media()->whereKey($media->id)->exists());

        $this->actingAs($this->teacher)
            ->get(route('teacher.questions.media.show', ['media' => $media->id]))
            ->assertOk();

        $this->actingAs($this->teacher)
            ->delete(route('teacher.questions.media.detach', [
                'question' => $this->question->id,
                'version' => $this->version->id,
                'media' => $media->id,
            ]))
            ->assertRedirect(route('teacher.questions.index', ['edit_question' => $this->question->id]));

        $this->assertFalse($this->version->fresh()->media()->whereKey($media->id)->exists());
    }

    public function test_question_bank_shows_media_controls_only_for_draft_versions(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(QuestionBank::class)
            ->call('openEditModal', $this->question->id)
            ->assertSee('Media Soal')
            ->assertSee('Unggah & Pasang Media', false)
            ->assertSee(route('teacher.questions.media.store', [
                'question' => $this->question->id,
                'version' => $this->version->id,
            ]));

        $this->version->update(['status' => 'PUBLISHED']);

        Livewire::actingAs($this->teacher)
            ->test(QuestionBank::class)
            ->call('openEditModal', $this->question->id)
            ->assertSee('Media Soal')
            ->assertSee('Versi ini sudah terbit')
            ->assertDontSee('Unggah & Pasang Media', false);
    }

    public function test_published_question_version_rejects_media_and_cleans_unattached_upload(): void
    {
        Storage::fake('local');
        $this->version->update(['status' => 'PUBLISHED']);

        $this->actingAs($this->teacher)->post(
            route('teacher.questions.media.store', [
                'question' => $this->question->id,
                'version' => $this->version->id,
            ]),
            [
                'file' => UploadedFile::fake()->image('published.png'),
                'role' => 'STEM',
                'sequence' => 0,
            ]
        )->assertStatus(409);

        $this->assertSame(0, MediaAsset::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
