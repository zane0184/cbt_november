<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use App\Contexts\TenantContext;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

final class TeacherResultNavigationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;

    private Tenant $tenant;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Tenant Navigasi Guru', 'code' => 'TNG01', 'status' => 'ACTIVE']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $role = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Navigasi',
            'email' => 'guru.navigasi@test.local',
            'password' => bcrypt('password123'),
        ]);
        $this->teacher->roles()->attach($role->id);
        $this->createTeacherExamScope($this->tenant, $this->teacher, [], 'NAVIGATION');
    }

    public function test_result_and_analytics_routes_have_stable_teacher_names(): void
    {
        $routes = Route::getRoutes();

        $this->assertSame('exams/{exam}/results', $routes->getByName('teacher.exams.results')?->uri());
        $this->assertSame('exams/{exam}/results/{attempt}', $routes->getByName('teacher.exams.results.detail')?->uri());
        $this->assertSame('exams/{exam}/analytics', $routes->getByName('teacher.exams.analytics')?->uri());
        $this->assertSame('exams/{exam}/analytics/items', $routes->getByName('teacher.exams.analytics.items')?->uri());
    }

    public function test_teacher_sidebar_links_results_and_analytics_to_exam_selection(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Penilaian & Hasil')
            ->assertSee('Analytics & Ekspor')
            ->assertSee(route('teacher.exams.index'), false)
            ->assertDontSee('aria-disabled="true" title="Penilaian dan hasil dibuka dari ujian');
    }

    public function test_named_result_and_analytics_urls_resolve_for_an_exam(): void
    {
        $examId = '018f0000-0000-7000-8000-000000000001';
        $attemptId = '018f0000-0000-7000-8000-000000000002';

        $this->assertSame('/exams/'.$examId.'/results', route('teacher.exams.results', ['exam' => $examId], false));
        $this->assertSame('/exams/'.$examId.'/results/'.$attemptId, route('teacher.exams.results.detail', [
            'exam' => $examId,
            'attempt' => $attemptId,
        ], false));
        $this->assertSame('/exams/'.$examId.'/analytics', route('teacher.exams.analytics', ['exam' => $examId], false));
        $this->assertSame('/exams/'.$examId.'/analytics/items', route('teacher.exams.analytics.items', ['exam' => $examId], false));
    }
}
