<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacySuperAdminInventoryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_finds_role_pivot_and_legacy_column_without_mutating_records(): void
    {
        $tenant = Tenant::create(['name' => 'Inventory Tenant', 'code' => 'INV001']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $pivotUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Legacy Pivot',
            'email' => 'pivot@example.test',
            'password' => bcrypt('secret'),
        ]);
        $legacyUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Legacy Column',
            'email' => 'column@example.test',
            'password' => bcrypt('secret'),
        ]);
        $normalUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Normal User',
            'email' => 'normal@example.test',
            'password' => bcrypt('secret'),
        ]);

        $superAdmin = Role::create(['name' => 'SUPER_ADMIN', 'label' => 'Legacy Super Admin']);
        $pivotUser->roles()->attach($superAdmin->id);
        DB::table('users')->where('id', $legacyUser->id)->update(['role' => 'SUPER_ADMIN']);

        $pivotCountBefore = DB::table('role_user')->count();
        $roleCountBefore = DB::table('roles')->count();
        $userCountBefore = DB::table('users')->count();

        $this->artisan('cbt:inventory-legacy-super-admin')
            ->expectsOutputToContain('Jumlah akun yang cocok: 2')
            ->assertExitCode(0);

        $this->assertSame($pivotCountBefore, DB::table('role_user')->count());
        $this->assertSame($roleCountBefore, DB::table('roles')->count());
        $this->assertSame($userCountBefore, DB::table('users')->count());
        $this->assertSame('SUPER_ADMIN', DB::table('users')->where('id', $legacyUser->id)->value('role'));
        $this->assertDatabaseHas('role_user', ['user_id' => $pivotUser->id, 'role_id' => $superAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $normalUser->id, 'role' => 'STUDENT']);
    }

    public function test_inventory_report_masks_email_and_is_written_only_inside_private_report_directory(): void
    {
        $tenant = Tenant::create(['name' => 'Private Report Tenant', 'code' => 'INV002']);
        app(TenantContext::class)->setTenantId($tenant->id);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Private Legacy',
            'email' => 'private@example.test',
            'password' => bcrypt('secret'),
        ]);
        $role = Role::create(['name' => 'SUPER_ADMIN', 'label' => 'Legacy Super Admin']);
        $user->roles()->attach($role->id);

        $filename = 'legacy-inventory-test.json';
        $path = storage_path('app/private/security-reports/' . $filename);

        try {
            $this->artisan('cbt:inventory-legacy-super-admin', ['--output' => $filename])
                ->expectsOutputToContain('Jumlah akun yang cocok: 1')
                ->assertExitCode(0);

            $this->assertFileExists($path);
            $contents = file_get_contents($path);
            $this->assertIsString($contents);
            $this->assertStringContainsString('p***@example.test', $contents);
            $this->assertStringNotContainsString('private@example.test', $contents);
            $this->assertStringContainsString('"database_mutations": false', $contents);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function test_inventory_does_not_overwrite_an_existing_report(): void
    {
        $filename = 'legacy-inventory-existing.json';
        $directory = storage_path('app/private/security-reports');
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($path, 'keep-existing-report');

        try {
            $this->artisan('cbt:inventory-legacy-super-admin', ['--output' => $filename])
                ->expectsOutputToContain('File laporan sudah ada')
                ->assertExitCode(1);
            $this->assertSame('keep-existing-report', file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_inventory_output_rejects_path_traversal_filename(): void
    {
        $this->artisan('cbt:inventory-legacy-super-admin', ['--output' => '../leak.json'])
            ->expectsOutputToContain('Nama output tidak valid')
            ->assertExitCode(2);
    }
}
