<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            throw new RuntimeException('Tenant branding assets require the tenants table.');
        }

        $hasDocumentLogo = Schema::hasColumn('tenants', 'document_logo_path');
        $hasFavicon = Schema::hasColumn('tenants', 'favicon_path');
        if ($hasDocumentLogo && $hasFavicon) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) use ($hasDocumentLogo, $hasFavicon): void {
            if (!$hasDocumentLogo) {
                $table->string('document_logo_path', 500)->nullable();
            }
            if (!$hasFavicon) {
                $table->string('favicon_path', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        foreach (['document_logo_path', 'favicon_path'] as $column) {
            if (Schema::hasColumn('tenants', $column) && DB::table('tenants')->whereNotNull($column)->exists()) {
                throw new LogicException("Tenant branding rollback blocked: {$column} contains stored asset paths.");
            }
        }

        $columns = array_values(array_filter(
            ['document_logo_path', 'favicon_path'],
            fn (string $column): bool => Schema::hasColumn('tenants', $column)
        ));
        if ($columns !== []) {
            Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
