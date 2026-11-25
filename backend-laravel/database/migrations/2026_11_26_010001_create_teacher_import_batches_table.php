<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_import_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('uploaded_by');
            $table->char('file_sha256', 64);
            $table->char('idempotency_key', 64);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('imported_rows')->default(0);
            $table->string('status', 20)->default('PROCESSING');
            $table->timestamps();

            $table->unique(['tenant_id', 'idempotency_key'], 'teacher_import_batches_tenant_idempotency_unique');
            $table->index(['tenant_id', 'created_at'], 'teacher_import_batches_tenant_created_idx');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign(['uploaded_by', 'tenant_id'], 'teacher_import_batches_uploader_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_import_batches');
    }
};
