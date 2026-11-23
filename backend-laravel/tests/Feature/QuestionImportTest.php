<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_question_row_import_endpoint_is_not_registered(): void
    {
        $tenant = Tenant::create(['name' => 'T1', 'code' => 'T1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);

        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Guru',
            'email' => 'guru@example.test',
            'password' => 'password',
        ]);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $teacher->roles()->attach($guruRole);

        $questionManage = Permission::firstOrCreate(
            ['name' => 'question.manage'],
            ['label' => 'Kelola Bank Soal'],
        );
        $guruRole->permissions()->syncWithoutDetaching([$questionManage->id]);

        $this->actingAs($teacher);

        $this->assertSame(
            0,
            collect(app('router')->getRoutes()->getRoutes())
                ->filter(fn ($route) => $route->uri() === 'api/v1/questions/import')
                ->count(),
            'The legacy rows-based question import route must remain disabled.',
        );
    }
}
