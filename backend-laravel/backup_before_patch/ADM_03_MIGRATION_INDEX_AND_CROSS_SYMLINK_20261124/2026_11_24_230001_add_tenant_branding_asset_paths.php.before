<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        // This migration may have encountered columns that predated it. Without
        // persisted ownership metadata, dropping an empty column could still
        // destroy a pre-existing schema contract. Keep this migration forward-only.
        throw new LogicException(
            'Tenant branding asset columns are forward-only; rollback requires an explicit reviewed schema change.'
        );
    }
};
