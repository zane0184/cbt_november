<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Import\ImportWorkbookService;
use Tests\TestCase;

class ImportDeadlockRetryTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseMigrations;

    public function test_import_row_transaction_retries_a_mysql_deadlock_without_partial_duplicate_account(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Deadlock', 'code' => 'DLOCK']);
        $this->setTenantContext($tenant->id);
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $permission = Permission::create(['name' => 'user.manage', 'label' => 'Kelola Pengguna']);
        $adminRole->permissions()->attach($permission->id);
        $admin = User::create([
            'name' => 'Admin Deadlock',
            'email' => 'admin.deadlock@madrasah.test',
            'password' => \Illuminate\Support\Facades\Hash::make('StrongPassword123!'),
        ]);
        $admin->roles()->attach($adminRole);
        $this->actingAs($admin);
        Role::create(['name' => 'GURU', 'label' => 'Guru']);

        $batch = \App\Models\ImportBatch::create([
            'created_by' => $admin->id,
            'type' => 'GURU',
            'template_version' => \App\Exports\Import\ImportTemplateVersion::VERSION,
            'file_sha256' => hash('sha256', 'deadlock-test'),
            'original_filename' => 'deadlock-test.xlsx',
            'status' => 'PREVIEW',
            'total_rows' => 1,
            'valid_rows' => 1,
        ]);
        $payload = [
            'category' => 'PNS', 'nip' => '001234567890123456', 'nik' => '', 'nuptk' => '',
            'name' => 'Guru Deadlock Retry', 'gender' => 'L', 'email' => '', 'phone' => '',
        ];
        \App\Models\ImportBatchRow::create([
            'batch_id' => $batch->id,
            'sheet_name' => 'Data Guru',
            'row_number' => 2,
            'status' => 'VALID',
            'reason' => null,
            'row_summary' => ['name' => $payload['name'], 'identity_suffix' => '3456'],
            'encrypted_payload' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
        ]);

        $attempts = 0;
        TeacherProfile::creating(function ($profile) use (&$attempts): void {
            $attempts++;
            if ($attempts === 1) {
                $deadlock = new \PDOException('Deadlock found when trying to get lock; try restarting transaction', 1213);
                throw new \Illuminate\Database\QueryException('mysql', 'insert into teacher_profiles', [], $deadlock);
            }
        });

        try {
            $result = app(ImportWorkbookService::class)->confirm($batch->id, $tenant->id, $admin->id);
        } finally {
            TeacherProfile::flushEventListeners();
        }

        $this->assertGreaterThanOrEqual(2, $attempts, 'Deadlock MySQL 1213 harus memicu retry transaksi baris tingkat atas.');
        $this->assertSame('COMPLETED', $result['batch']->status);
        $this->assertSame(1, TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('nip', '001234567890123456')->count());
        $this->assertSame(1, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('name', 'Guru Deadlock Retry')->count());
    }
}
