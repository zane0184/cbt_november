<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\LearningOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_cp_api_keeps_read_routes_but_disables_mutations(): void
    {
        $tenant = Tenant::create(['name' => 'Demo', 'code' => 'DEMO']);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $routes = collect(app('router')->getRoutes()->getRoutes());
        $this->assertSame(1, $routes->filter(
            fn ($route) => $route->uri() === 'api/v1/learning-outcomes' && in_array('GET', $route->methods(), true)
        )->count());
        $this->assertSame(0, $routes->filter(
            fn ($route) => $route->uri() === 'api/v1/learning-outcomes' && in_array('POST', $route->methods(), true)
        )->count());
        $this->assertSame(0, $routes->filter(
            fn ($route) => $route->uri() === 'api/v1/learning-outcomes/{learning_outcome}'
                && array_intersect(['PUT', 'PATCH', 'DELETE'], $route->methods()) !== []
        )->count());
        $this->assertSame(0, LearningOutcome::query()->count());
    }

    public function test_legacy_academic_master_resources_are_read_only(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        foreach (['curricula', 'phases', 'grade-levels', 'elements'] as $resource) {
            $baseUri = 'api/v1/'.$resource;
            $this->assertSame(1, $routes->filter(
                fn ($route) => $route->uri() === $baseUri && in_array('GET', $route->methods(), true)
            )->count(), "{$resource} must retain its read-only index route.");
            $this->assertSame(0, $routes->filter(
                fn ($route) => $route->uri() === $baseUri
                    && array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== []
            )->count(), "{$resource} must not expose collection mutation routes.");
            $this->assertSame(1, $routes->filter(
                fn ($route) => str_starts_with($route->uri(), $baseUri.'/{')
                    && in_array('GET', $route->methods(), true)
            )->count(), "{$resource} must retain its read-only detail route.");
            $this->assertSame(0, $routes->filter(
                fn ($route) => str_starts_with($route->uri(), $baseUri.'/{')
                    && array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== []
            )->count(), "{$resource} must not expose item mutation routes.");
        }
    }

    public function test_legacy_tp_and_atp_write_routes_are_not_registered(): void
    {
        $tenant = Tenant::create(['name' => 'Demo', 'code' => 'DEMO']);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Guru',
            'email' => 'guru@example.test',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $routes = collect(app('router')->getRoutes()->getRoutes());
        foreach (['learning-objectives', 'learning-paths'] as $resource) {
            $this->assertSame(0, $routes->filter(
                fn ($route) => str_starts_with($route->uri(), 'api/v1/'.$resource)
                    && array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== []
            )->count(), "Legacy {$resource} write routes must remain disabled.");
        }
    }
}
