<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username', 80)->nullable()->after('name');
            });
        }
        if (! Schema::hasColumn('teacher_profiles', 'nik')) {
            Schema::table('teacher_profiles', function (Blueprint $table): void {
                $table->string('nik', 16)->nullable()->after('nip');
            });
        }

        // Preserve legacy accounts while assigning deterministic, tenant-unique usernames.
        // Existing email remains contact/recovery data, not the login identifier.
        DB::table('users')->whereNull('username')->orderBy('id')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $local = strtolower(trim((string) strstr((string) $user->email, '@', true)));
                $candidate = preg_replace('/[^a-z0-9._-]+/', '-', $local) ?: '';
                $candidate = trim(substr($candidate, 0, 60), '._-');
                if ($candidate === '') $candidate = 'user';
                $base = $candidate;
                $suffix = strtolower(substr(str_replace('-', '', (string) $user->id), 0, 8));
                if (DB::table('users')->where('tenant_id', $user->tenant_id)->where('username', $candidate)->where('id', '<>', $user->id)->exists()) {
                    $candidate = substr($base, 0, 50).'_'.$suffix;
                }
                $attempt = 0;
                while (DB::table('users')->where('tenant_id', $user->tenant_id)->where('username', $candidate)->where('id', '<>', $user->id)->exists()) {
                    $attempt++;
                    $candidate = substr($base, 0, 44).'_'.$suffix.substr((string) $attempt, 0, 4);
                    if ($attempt > 9999) throw new RuntimeException('Unable to allocate unique tenant username during migration.');
                }
                DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
            }
        });

        $duplicate = DB::table('users')->select('tenant_id', 'username')->whereNotNull('username')->groupBy('tenant_id', 'username')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicate) throw new RuntimeException('Duplicate tenant username detected; unique index not created.');

        if (! Schema::hasIndex('users', 'users_tenant_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'username'], 'users_tenant_username_unique');
            });
        }

        // Email is optional contact/recovery data; unique nullable email remains tenant-scoped.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        } else {
            Schema::table('users', function (Blueprint $table): void { $table->string('email')->nullable()->change(); });
        }
        if (! Schema::hasIndex('teacher_profiles', 'teacher_profiles_tenant_nik_idx')) {
            Schema::table('teacher_profiles', function (Blueprint $table): void {
                $table->index(['tenant_id', 'nik'], 'teacher_profiles_tenant_nik_idx');
            });
        }
    }

    public function down(): void
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException('Rollback blocked: users.email contains NULL values; restore contact emails before rollback.');
        }
        Schema::table('teacher_profiles', function (Blueprint $table): void {
            $table->dropIndex('teacher_profiles_tenant_nik_idx');
            $table->dropColumn('nik');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_tenant_username_unique');
            $table->dropColumn('username');
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        } else {
            Schema::table('users', function (Blueprint $table): void { $table->string('email')->nullable(false)->change(); });
        }
    }
};
