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

        if (!Schema::hasColumn('tenants', 'document_logo_path')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('document_logo_path', 500)->nullable();
            });
        }

        if (!Schema::hasColumn('tenants', 'favicon_path')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('favicon_path', 500)->nullable();
            });
        }

        // These are non-unique reference indexes: shared branding assets are
        // valid, while deleteManaged() needs efficient reference lookups.
        $this->ensureReferenceIndex('logo_path', 'tenants_logo_path_index');
        $this->ensureReferenceIndex('document_logo_path', 'tenants_document_logo_path_index');
        $this->ensureReferenceIndex('favicon_path', 'tenants_favicon_path_index');
    }

    private function ensureReferenceIndex(string $column, string $indexName): void
    {
        if (!Schema::hasColumn('tenants', $column)) {
            throw new LogicException('Tenant branding reference column is missing: ' . $column);
        }

        foreach (Schema::getIndexes('tenants') as $index) {
            $columns = $index['columns'] ?? [];
            if (($columns[0] ?? null) !== $column) {
                continue;
            }

            if (($index['unique'] ?? false) === true) {
                throw new LogicException(
                    'Tenant branding reference indexes must be non-unique to preserve shared asset references: ' . $column
                );
            }

            return;
        }

        Schema::table('tenants', function (Blueprint $table) use ($column, $indexName): void {
            $table->index($column, $indexName);
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
