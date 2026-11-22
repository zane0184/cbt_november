<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_answer_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignUuid('exam_attempt_id');
            $table->foreign(['exam_attempt_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('exam_attempts')
                ->onDelete('restrict');
            $table->string('client_request_id', 255);
            $table->char('payload_hash', 64);
            $table->json('response_json');
            $table->timestamp('committed_at');
            $table->unique(['tenant_id', 'exam_attempt_id', 'client_request_id'], 'uniq_attempt_request_id');
            $table->index(['tenant_id', 'exam_attempt_id', 'committed_at'], 'idx_attempt_request_history');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Answer idempotency receipts are immutable execution history. This migration is forward-only.'
        );
    }
};
