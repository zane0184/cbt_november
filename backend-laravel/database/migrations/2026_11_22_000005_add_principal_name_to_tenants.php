<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenants', 'principal_name')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('principal_name')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'principal_name')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->dropColumn('principal_name');
            });
        }
    }
};
