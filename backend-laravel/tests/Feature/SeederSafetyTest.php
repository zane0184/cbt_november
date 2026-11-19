<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_catalog_only_and_no_demo_accounts_or_proctor_portal_role(): void
    {
        app(DatabaseSeeder::class)->run();

        $this->assertSame(0, Tenant::withoutGlobalScopes()->count());
        $this->assertSame(0, User::withoutGlobalScopes()->count());
        $this->assertDatabaseMissing('roles', ['name' => 'SUPER_ADMIN']);
        $this->assertDatabaseMissing('roles', ['name' => 'PROKTOR']);
        $this->assertDatabaseHas('roles', ['name' => 'ADMIN_MADRASAH']);
        $this->assertDatabaseHas('roles', ['name' => 'GURU']);
        $this->assertDatabaseHas('roles', ['name' => 'KAMAD']);
        $this->assertDatabaseHas('permissions', ['name' => 'curriculum.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'curriculum.manage']);
        $this->assertDatabaseHas('permissions', ['name' => 'academic.manage']);
    }

    public function test_demo_data_seeder_is_excluded_from_distributable_source(): void
    {
        $this->assertFalse(class_exists('Database\\Seeders\\DemoDataSeeder'));
    }
}
