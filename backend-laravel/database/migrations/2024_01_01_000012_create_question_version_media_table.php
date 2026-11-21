<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_version_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('question_version_id')->constrained('question_versions')->onDelete('cascade');
            $table->foreignUuid('media_asset_id')->constrained('media_assets')->onDelete('cascade');
            $table->string('role')->default('STEM'); // STEM, OPTION, EXPLANATION
            $table->integer('sequence')->default(0);
            $table->timestamps();

            $table->foreign(['question_version_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('question_versions');
            $table->foreign(['media_asset_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('media_assets');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_version_media');
    }
};
