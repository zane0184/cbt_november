<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignUuid('participant_id')->constrained('exam_participants')->onDelete('cascade');

            $table->integer('attempt_number');
            $table->string('status')->default('NOT_STARTED'); // IN_PROGRESS, SUBMITTED, EXPIRED

            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->string('server_start_reference')->nullable(); // Track server-time sync
            $table->timestamps();

            $table->unique(['participant_id', 'attempt_number']);
            $table->unique(['id', 'tenant_id']);
            $table->foreign(['exam_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exams');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
