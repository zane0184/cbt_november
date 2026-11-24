<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class LoginIdentityDatabaseConstraintTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Identity Constraint Test', 'code' => 'IDCHK']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    public function test_mysql_check_constraints_reject_invalid_raw_sql_insert_and_update(): void
    {
        $validNip = '198001012006041001';
        $validTeacher = $this->makeUser('teacher.valid');
        $validTeacherId = (string) Str::uuid();
        DB::table('teacher_profiles')->insert($this->teacherRow(
            $validTeacher,
            $validTeacherId,
            'PNS',
            $validNip,
            null,
            null,
            $validNip
        ));

        $validHonorer = $this->makeUser('teacher.honorer');
        DB::table('teacher_profiles')->insert($this->teacherRow(
            $validHonorer,
            (string) Str::uuid(),
            'HONORER',
            null,
            '3171000000000001',
            null,
            '3171000000000001'
        ));

        $validStudent = $this->makeUser('student.valid');
        $validStudentId = (string) Str::uuid();
        DB::table('student_profiles')->insert($this->studentRow($validStudent, $validStudentId, '9000000001'));

        $shortNipUser = $this->makeUser('teacher.shortnip');
        $this->assertQueryRejected(fn () => DB::table('teacher_profiles')->insert($this->teacherRow(
            $shortNipUser,
            (string) Str::uuid(),
            'PNS',
            str_repeat('1', 17),
            null,
            null,
            str_repeat('1', 17)
        )));

        $wrongCategoryUser = $this->makeUser('teacher.wrongcategory');
        $this->assertQueryRejected(fn () => DB::table('teacher_profiles')->insert($this->teacherRow(
            $wrongCategoryUser,
            (string) Str::uuid(),
            'PNS',
            '198001012006041002',
            '3171000000000002',
            null,
            '198001012006041002'
        )));

        $honorerWithNipUser = $this->makeUser('teacher.honorerwithnip');
        $this->assertQueryRejected(fn () => DB::table('teacher_profiles')->insert($this->teacherRow(
            $honorerWithNipUser,
            (string) Str::uuid(),
            'HONORER',
            '198001012006041003',
            null,
            null,
            '198001012006041003'
        )));

        $badNuptkUser = $this->makeUser('teacher.badnuptk');
        $this->assertQueryRejected(fn () => DB::table('teacher_profiles')->insert($this->teacherRow(
            $badNuptkUser,
            (string) Str::uuid(),
            'PNS',
            '198001012006041004',
            null,
            '12345',
            '198001012006041004'
        )));

        $mismatchedIdentifierUser = $this->makeUser('teacher.mismatch');
        $this->assertQueryRejected(fn () => DB::table('teacher_profiles')->insert($this->teacherRow(
            $mismatchedIdentifierUser,
            (string) Str::uuid(),
            'PNS',
            '198001012006041005',
            null,
            null,
            '198001012006041006'
        )));

        $badStudent = $this->makeUser('student.badnisn');
        $this->assertQueryRejected(fn () => DB::table('student_profiles')->insert($this->studentRow(
            $badStudent,
            (string) Str::uuid(),
            'NISN-BAD'
        )));

        $this->assertQueryRejected(fn () => DB::table('teacher_profiles')
            ->where('id', $validTeacherId)
            ->update([
                'nip' => str_repeat('2', 17),
                'employee_identifier' => str_repeat('2', 17),
            ]));

        $this->assertQueryRejected(fn () => DB::table('student_profiles')
            ->where('id', $validStudentId)
            ->update(['nisn' => 'BAD']));

        $this->assertDatabaseHas('teacher_profiles', ['id' => $validTeacherId, 'nip' => $validNip]);
        $this->assertDatabaseHas('student_profiles', ['id' => $validStudentId, 'nisn' => '9000000001']);
    }

    public function test_migration_preflight_refuses_legacy_invalid_rows_without_mutating_them(): void
    {
        DB::statement('SET SESSION check_constraint_checks = OFF');

        $user = $this->makeUser('teacher.legacy');
        $id = (string) Str::uuid();
        $invalidRow = $this->teacherRow(
            $user,
            $id,
            'PNS',
            str_repeat('7', 17),
            null,
            null,
            str_repeat('7', 17)
        );

        try {
            DB::table('teacher_profiles')->insert($invalidRow);

            $migration = require database_path('migrations/2026_11_24_180001_enforce_login_identity_format.php');
            $exception = null;
            try {
                $migration->up();
            } catch (RuntimeException $caught) {
                $exception = $caught;
            }

            $this->assertInstanceOf(RuntimeException::class, $exception);
            $this->assertStringContainsString('existing teacher profile', $exception->getMessage());
            $this->assertDatabaseHas('teacher_profiles', ['id' => $id, 'nip' => str_repeat('7', 17)]);
        } finally {
            DB::table('teacher_profiles')->where('id', $id)->delete();
            DB::statement('SET SESSION check_constraint_checks = ON');
        }
    }

    private function makeUser(string $username): User
    {
        return User::create([
            'tenant_id' => $this->tenant->id,
            'name' => $username,
            'username' => $username,
            'email' => $username . '@identity.test',
            'password' => bcrypt('password123456'),
        ]);
    }

    private function teacherRow(
        User $user,
        string $id,
        string $status,
        ?string $nip,
        ?string $nik,
        ?string $nuptk,
        ?string $employeeIdentifier
    ): array {
        return [
            'id' => $id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'employment_status' => $status,
            'nip' => $nip,
            'nik' => $nik,
            'nuptk' => $nuptk,
            'employee_identifier' => $employeeIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function studentRow(User $user, string $id, string $nisn): array
    {
        return [
            'id' => $id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'nisn' => $nisn,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function assertQueryRejected(callable $callback): void
    {
        $rejected = false;
        try {
            $callback();
        } catch (QueryException) {
            $rejected = true;
        }

        $this->assertTrue($rejected, 'The database must reject malformed login identity writes.');
    }
}
