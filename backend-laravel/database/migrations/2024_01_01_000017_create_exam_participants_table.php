<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade'); // Student identity

            $table->string('status')->default('ASSIGNED'); // ASSIGNED, STARTED, COMPLETED, EXPIRED
            $table->timestamp('eligible_from')->nullable();
            $table->timestamp('eligible_until')->nullable();
            $table->integer('attempt_limit_override')->nullable();

            $table->timestamps();

            $table->unique(['exam_id', 'user_id']);
            $table->foreign(['exam_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exams');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_participants');
    }
};
