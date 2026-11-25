<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'qvm_tenant_version_media_unique';

    public function up(): void
    {
        // Fail closed instead of silently deleting or merging historical links.
        $duplicatesExist = DB::table('question_version_media')
            ->select('tenant_id', 'question_version_id', 'media_asset_id')
            ->groupBy('tenant_id', 'question_version_id', 'media_asset_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicatesExist) {
            throw new RuntimeException(
                'Cannot add question-version media uniqueness: duplicate references exist. Audit and reconcile them before migration.'
            );
        }

        Schema::table('question_version_media', function (Blueprint $table) {
            $table->unique(
                ['tenant_id', 'question_version_id', 'media_asset_id'],
                self::INDEX_NAME
            );
        });
    }

    public function down(): void
    {
        Schema::table('question_version_media', function (Blueprint $table) {
            $table->dropUnique(self::INDEX_NAME);
        });
    }
};
