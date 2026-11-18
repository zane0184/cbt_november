<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_publications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('question_id')->constrained('questions')->onDelete('cascade');
            $table->foreignUuid('source_tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('source_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('share_scope')->default('MADRASAH'); // MADRASAH, ALL_MADRASAH
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REVOKED
            $table->foreignUuid('published_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign(['question_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('questions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_publications');
    }
};
