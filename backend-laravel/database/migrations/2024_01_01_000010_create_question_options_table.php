<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('question_version_id')->constrained('question_versions')->onDelete('cascade');
            $table->string('option_key')->nullable(); // e.g., A, B, C or specific keys
            $table->text('content');
            $table->integer('sequence')->default(0);
            $table->boolean('is_correct')->default(false);
            $table->decimal('score_weight', 5, 2)->default(0.00);
            $table->string('matching_key')->nullable(); // For matching type
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign(['question_version_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('question_versions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
