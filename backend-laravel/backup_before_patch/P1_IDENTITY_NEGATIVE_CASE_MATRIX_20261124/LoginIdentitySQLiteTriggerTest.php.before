<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use RuntimeException;
use Tests\TestCase;

class LoginIdentitySQLiteTriggerTest extends TestCase
{
    public function test_sqlite_triggers_enforce_login_identity_and_migration_preflight_is_fail_closed(): void
    {
        $originalDefault = config('database.default');
        config([
            'database.connections.identity_sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.default' => 'identity_sqlite',
        ]);
        DB::purge('identity_sqlite');

        try {
            Schema::create('teacher_profiles', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('tenant_id');
                $table->string('user_id');
                $table->string('full_name');
                $table->string('employment_status')->nullable();
                $table->string('employee_identifier')->nullable();
                $table->string('nip')->nullable();
                $table->string('nik')->nullable();
                $table->string('nuptk')->nullable();
                $table->timestamps();
            });

            Schema::create('student_profiles', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('tenant_id');
                $table->string('user_id');
                $table->string('full_name');
                $table->string('nisn')->nullable();
                $table->timestamps();
            });

            $migration = require database_path('migrations/2026_11_24_180001_enforce_login_identity_format.php');

            DB::table('teacher_profiles')->insert($this->teacherRow(
                'legacy-invalid',
                'PNS',
                str_repeat('1', 17),
                null,
                null,
                str_repeat('1', 17)
            ));

            $preflightException = null;
            try {
                $migration->up();
            } catch (RuntimeException $exception) {
                $preflightException = $exception;
            }

            $this->assertInstanceOf(RuntimeException::class, $preflightException);
            $this->assertStringContainsString('existing teacher profile', $preflightException->getMessage());
            $this->assertSame(0, DB::table('sqlite_master')->where('type', 'trigger')->count());
            $this->assertDatabaseHas('teacher_profiles', ['id' => 'legacy-invalid']);

            DB::table('teacher_profiles')->where('id', 'legacy-invalid')->delete();
            $migration->up();

            $triggerCount = DB::select("SELECT name FROM sqlite_master WHERE type = 'trigger'");
            $this->assertCount(4, $triggerCount);

            DB::table('teacher_profiles')->insert($this->teacherRow(
                'teacher-valid',
                'PNS',
                '198001012006041001',
                null,
                null,
                '198001012006041001'
            ));

            $this->assertQueryRejected(fn () => DB::table('teacher_profiles')->insert($this->teacherRow(
                'teacher-short-nip',
                'PNS',
                str_repeat('2', 17),
                null,
                null,
                str_repeat('2', 17)
            )));

            $this->assertQueryRejected(fn () => DB::table('teacher_profiles')->insert($this->teacherRow(
                'teacher-wrong-category',
                'PNS',
                '198001012006041002',
                '3171000000000001',
                null,
                '198001012006041002'
            )));

            $this->assertQueryRejected(fn () => DB::table('teacher_profiles')->insert($this->teacherRow(
                'teacher-bad-nuptk',
                'PNS',
                '198001012006041003',
                null,
                '12345',
                '198001012006041003'
            )));

            $this->assertQueryRejected(fn () => DB::table('teacher_profiles')
                ->where('id', 'teacher-valid')
                ->update(['nip' => str_repeat('3', 17), 'employee_identifier' => str_repeat('3', 17)]));

            DB::table('student_profiles')->insert([
                'id' => 'student-valid',
                'tenant_id' => 'tenant-1',
                'user_id' => 'user-student-valid',
                'full_name' => 'Siswa Valid',
                'nisn' => '0001234567',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertQueryRejected(fn () => DB::table('student_profiles')->insert([
                'id' => 'student-invalid',
                'tenant_id' => 'tenant-1',
                'user_id' => 'user-student-invalid',
                'full_name' => 'Siswa Invalid',
                'nisn' => 'NISN-BAD',
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $this->assertQueryRejected(fn () => DB::table('student_profiles')
                ->where('id', 'student-valid')
                ->update(['nisn' => 'BAD']));
        } finally {
            Schema::dropIfExists('student_profiles');
            Schema::dropIfExists('teacher_profiles');
            DB::purge('identity_sqlite');
            config(['database.default' => $originalDefault]);
        }
    }

    private function teacherRow(
        string $id,
        string $status,
        ?string $nip,
        ?string $nik,
        ?string $nuptk,
        ?string $employeeIdentifier
    ): array {
        return [
            'id' => $id,
            'tenant_id' => 'tenant-1',
            'user_id' => 'user-' . $id,
            'full_name' => 'Guru ' . $id,
            'employment_status' => $status,
            'nip' => $nip,
            'nik' => $nik,
            'nuptk' => $nuptk,
            'employee_identifier' => $employeeIdentifier,
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

        $this->assertTrue($rejected, 'SQLite trigger must reject malformed login identity writes.');
    }
}
