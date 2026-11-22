<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batch_rows', function (Blueprint $table): void {
            $table->longText('encrypted_payload')->nullable()->after('row_summary');
        });
    }

    public function down(): void
    {
        Schema::table('import_batch_rows', function (Blueprint $table): void {
            $table->dropColumn('encrypted_payload');
        });
    }
};
