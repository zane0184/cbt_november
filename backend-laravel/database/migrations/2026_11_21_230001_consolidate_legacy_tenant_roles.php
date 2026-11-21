<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('role_user') || !Schema::hasTable('permission_role')) {
            throw new \RuntimeException('Role consolidation requires the roles and pivot tables.');
        }

        if (!Schema::hasTable('legacy_role_migration_reviews')) {
            Schema::create('legacy_role_migration_reviews', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('legacy_role', 64);
                $table->text('reason');
                $table->string('status', 24)->default('PENDING');
                $table->timestamps();
                $table->unique(['user_id', 'legacy_role'], 'legacy_role_review_user_role_unique');
            });
        }

        DB::transaction(function (): void {
            $adminRole = DB::table('roles')->where('name', 'ADMIN_MADRASAH')->first();
            $guruRole = DB::table('roles')->where('name', 'GURU')->first();

            // ADMIN and OPERATOR are legacy aliases of one tenant Admin identity.
            // Do not union their old permissions into the canonical Admin role.
            foreach (['ADMIN', 'OPERATOR'] as $legacyName) {
                $legacyRole = DB::table('roles')->where('name', $legacyName)->first();
                if (!$legacyRole) {
                    continue;
                }

                $members = DB::table('role_user')->where('role_id', $legacyRole->id)->pluck('user_id');
                if ($members->isNotEmpty() && !$adminRole) {
                    throw new \RuntimeException('ADMIN_MADRASAH role must exist before migrating legacy '.$legacyName.' accounts.');
                }

                foreach ($members as $userId) {
                    $alreadyAttached = DB::table('role_user')
                        ->where('role_id', $adminRole->id)
                        ->where('user_id', $userId)
                        ->exists();

                    if ($alreadyAttached) {
                        DB::table('role_user')->where('role_id', $legacyRole->id)->where('user_id', $userId)->delete();
                    } else {
                        DB::table('role_user')->where('role_id', $legacyRole->id)->where('user_id', $userId)
                            ->update(['role_id' => $adminRole->id]);
                    }
                }

                DB::table('permission_role')->where('role_id', $legacyRole->id)->delete();
                DB::table('roles')->where('id', $legacyRole->id)->delete();
            }

            // PROKTOR is not a permanent role. Only accounts with an active,
            // non-deleted TeacherProfile are promoted to GURU. Others are retained
            // under a non-authorizing review role and recorded for manual review.
            $legacyProktor = DB::table('roles')->where('name', 'PROKTOR')->first();
            if (!$legacyProktor) {
                return;
            }

            $proktorMembers = DB::table('role_user')->where('role_id', $legacyProktor->id)->pluck('user_id');
            $reviewCount = 0;

            foreach ($proktorMembers as $userId) {
                $hasActiveTeacherProfile = Schema::hasTable('teacher_profiles')
                    && DB::table('teacher_profiles')->where('user_id', $userId)->whereNull('deleted_at')->exists();

                if ($hasActiveTeacherProfile) {
                    if (!$guruRole) {
                        throw new \RuntimeException('GURU role must exist before converting an eligible legacy PROKTOR account.');
                    }

                    $alreadyGuru = DB::table('role_user')->where('role_id', $guruRole->id)->where('user_id', $userId)->exists();
                    if (!$alreadyGuru) {
                        DB::table('role_user')->insert(['role_id' => $guruRole->id, 'user_id' => $userId]);
                    }
                    DB::table('role_user')->where('role_id', $legacyProktor->id)->where('user_id', $userId)->delete();
                    continue;
                }

                $reviewCount++;
                DB::table('legacy_role_migration_reviews')->updateOrInsert(
                    ['user_id' => $userId, 'legacy_role' => 'PROKTOR'],
                    [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'reason' => 'TeacherProfile aktif tidak ditemukan; verifikasi identitas dan penugasan secara manual sebelum menetapkan role GURU.',
                        'status' => 'PENDING',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::table('permission_role')->where('role_id', $legacyProktor->id)->delete();

            if ($reviewCount > 0) {
                $existingReviewRole = DB::table('roles')->where('name', 'LEGACY_PROKTOR_REVIEW')->first();
                if ($existingReviewRole) {
                    $pendingUsers = DB::table('role_user')->where('role_id', $legacyProktor->id)->pluck('user_id');
                    foreach ($pendingUsers as $userId) {
                        $alreadyAttached = DB::table('role_user')
                            ->where('role_id', $existingReviewRole->id)
                            ->where('user_id', $userId)
                            ->exists();
                        if ($alreadyAttached) {
                            DB::table('role_user')->where('role_id', $legacyProktor->id)->where('user_id', $userId)->delete();
                        } else {
                            DB::table('role_user')->where('role_id', $legacyProktor->id)->where('user_id', $userId)
                                ->update(['role_id' => $existingReviewRole->id]);
                        }
                    }
                    DB::table('roles')->where('id', $legacyProktor->id)->delete();
                } else {
                    DB::table('roles')->where('id', $legacyProktor->id)->update([
                        'name' => 'LEGACY_PROKTOR_REVIEW',
                        'label' => 'Legacy Proktor — Perlu Review',
                        'updated_at' => now(),
                    ]);
                }
            } else {
                DB::table('roles')->where('id', $legacyProktor->id)->delete();
            }
        });
    }

    public function down(): void
    {
        throw new \LogicException('Legacy role consolidation is intentionally irreversible; restore from a verified backup instead of recreating legacy role grants.');
    }
};
