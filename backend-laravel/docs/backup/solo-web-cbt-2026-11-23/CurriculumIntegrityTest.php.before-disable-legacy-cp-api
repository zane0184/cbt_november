<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\LearningOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $userA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantA = Tenant::create(['name' => 'A', 'code' => 'A']);
        $this->tenantB = Tenant::create(['name' => 'B', 'code' => 'B']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);
        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin A',
            'email' => 'a@example.com',
            'password' => bcrypt('password')
        ]);
        // Simple mock permission check
        \Illuminate\Support\Facades\Gate::define('learning_outcome.manage', fn() => true);
    }

    public function test_cannot_create_cp_pointing_to_phase_of_another_tenant()
    {
        $this->actingAs($this->userA);

        $currA = Curriculum::create(['name' => 'Curr A', 'code' => 'A']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantB->id);
        $currB = Curriculum::create(['name' => 'Curr B', 'code' => 'B']);
        $phaseB = Phase::create(['curriculum_id' => $currB->id, 'name' => 'Phase B', 'code' => 'B']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);

        $response = $this->postJson('/api/v1/learning-outcomes', [
            'curriculum_id' => $currA->id,
            'phase_id' => $phaseB->id,
            'subject_id' => \Illuminate\Support\Str::uuid(), // Invalid subject
            'code' => 'FAIL',
            'title' => 'Fail',
            'description' => 'Should fail validation'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phase_id', 'subject_id']);
    }

    public function test_cp_versioning_immutability()
    {
        $this->actingAs($this->userA);

        $currA = Curriculum::create(['name' => 'Curr A', 'code' => 'A']);
        $phaseA = Phase::create(['curriculum_id' => $currA->id, 'name' => 'Phase A', 'code' => 'A']);
        $subjectA = \App\Models\Subject::create(['code' => 'S1', 'name' => 'S1']);

        // Create V1
        $cp = LearningOutcome::create([
            'curriculum_id' => $currA->id,
            'phase_id' => $phaseA->id,
            'subject_id' => $subjectA->id,
            'code' => 'CP.1'
        ]);
        $v1 = $cp->versions()->create([
            'major_version' => 1,
            'minor_version' => 0,
            'title' => 'Original',
            'description' => 'Original Desc',
            'is_current' => true,
            'tenant_id' => $this->tenantA->id
        ]);

        // Trigger New Version via Update
        $this->putJson("/api/v1/learning-outcomes/{$cp->id}", [
            'title' => 'Revised',
            'description' => 'New Desc',
            'create_new_version' => true
        ]);

        $v1->refresh();
        $this->assertEquals('Original Desc', $v1->description);
        $this->assertFalse((bool) $v1->is_current);

        $this->assertDatabaseHas('learning_outcome_versions', [
            'learning_outcome_id' => $cp->id,
            'minor_version' => 1,
            'description' => 'New Desc',
            'is_current' => true
        ]);
    }
}
