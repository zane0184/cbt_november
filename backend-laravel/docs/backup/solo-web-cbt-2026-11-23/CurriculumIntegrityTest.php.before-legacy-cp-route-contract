<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\LearningOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_cp_write_route_cannot_create_a_record(): void
    {
        $tenant = Tenant::create(['name' => 'A', 'code' => 'A']);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin A',
            'email' => 'a@example.test',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $before = LearningOutcome::query()->count();
        $response = $this->postJson('/api/v1/learning-outcomes', [
            'curriculum_id' => '00000000-0000-4000-8000-000000000001',
            'phase_id' => '00000000-0000-4000-8000-000000000002',
            'subject_id' => '00000000-0000-4000-8000-000000000003',
            'code' => 'LEGACY-BLOCKED',
            'title' => 'Must not be written through legacy API',
            'description' => 'Official CP is read-only.',
        ]);

        $response->assertStatus(405);
        $this->assertSame($before, LearningOutcome::query()->count());
    }
}
