<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('created_by');
            $table->string('type', 10);
            $table->string('template_version', 20);
            $table->char('file_sha256', 64);
            $table->string('original_filename', 255);
            $table->string('status', 20)->default('PREVIEW');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'type', 'file_sha256'], 'import_batches_tenant_file_unique');
            $table->unique(['id', 'tenant_id'], 'import_batches_id_tenant_unique');
            $table->foreign(['created_by', 'tenant_id'], 'import_batches_creator_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'created_at'], 'import_batches_tenant_created_idx');
        });

        Schema::create('import_batch_rows', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('batch_id');
            $table->string('sheet_name', 40);
            $table->unsignedInteger('row_number');
            $table->string('status', 20);
            $table->string('reason', 500)->nullable();
            $table->json('row_summary')->nullable();
            $table->timestamps();
            $table->unique(['batch_id', 'sheet_name', 'row_number'], 'import_batch_rows_location_unique');
            $table->foreign(['batch_id', 'tenant_id'], 'import_batch_rows_batch_tenant_fk')
                ->references(['id', 'tenant_id'])->on('import_batches')->cascadeOnDelete();
            $table->index(['tenant_id', 'batch_id', 'status'], 'import_batch_rows_status_idx');
        });

        Schema::create('import_activation_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('user_id');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign(['user_id', 'tenant_id'], 'import_activation_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->cascadeOnDelete();
            $table->index(['tenant_id', 'user_id', 'used_at'], 'import_activation_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_activation_tokens');
        Schema::dropIfExists('import_batch_rows');
        Schema::dropIfExists('import_batches');
    }
};
