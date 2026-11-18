<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('owner_type'); // e.g., QUESTION, USER
            $table->uuid('owner_id')->nullable();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->string('extension');
            $table->unsignedBigInteger('file_size');
            $table->string('checksum')->nullable();
            $table->string('disk')->default('local');
            $table->json('metadata')->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id']);
            $table->index(['tenant_id', 'owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
