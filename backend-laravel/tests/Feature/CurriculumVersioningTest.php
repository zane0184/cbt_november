<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\Subject;
use App\Models\LearningOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumVersioningTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $user;
    private $curr;
    private $phase;
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Demo', 'code' => 'DEMO']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);
        \Illuminate\Support\Facades\Gate::define('learning_outcome.manage', fn() => true);

        // Setup initial curriculum
        $this->curr = Curriculum::create(['name' => 'K-Merdeka', 'code' => 'KM']);
        $this->phase = Phase::create(['curriculum_id' => $this->curr->id, 'name' => 'Fase A', 'code' => 'A']);
        $this->subject = Subject::create(['code' => 'MAT', 'name' => 'Math']);
    }

    public function test_can_create_cp_with_initial_version()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/learning-outcomes', [
            'curriculum_id' => $this->curr->id,
            'phase_id' => $this->phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP.001',
            'title' => 'CP Math V1',
            'description' => 'Original Description',
            'version' => '1.0'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('learning_outcomes', ['code' => 'CP.001']);
        $this->assertDatabaseHas('learning_outcome_versions', ['major_version' => 1, 'minor_version' => 0, 'is_current' => 1]);
    }

    public function test_can_create_new_version_of_cp()
    {
        $this->actingAs($this->user);

        // 1. Create original
        $cp = LearningOutcome::create([
            'curriculum_id' => $this->curr->id,
            'phase_id' => $this->phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP.002'
        ]);
        $cp->versions()->create([
            'major_version' => 1,
            'minor_version' => 0,
            'title' => 'V1',
            'description' => 'Desc 1',
            'is_current' => true,
            'tenant_id' => $this->tenant->id
        ]);

        // 2. Update to V2
        $response = $this->putJson("/api/v1/learning-outcomes/{$cp->id}", [
            'title' => 'V2',
            'description' => 'Desc 2',
            'create_new_version' => true
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('learning_outcome_versions', ['major_version' => 1, 'minor_version' => 0, 'is_current' => 0]);
        $this->assertDatabaseHas('learning_outcome_versions', ['major_version' => 1, 'minor_version' => 1, 'is_current' => 1]);
    }
}
