<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_academic_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('student_id');
            $table->uuid('academic_year_id')->nullable();
            $table->uuid('from_rombel_id')->nullable();
            $table->uuid('to_rombel_id')->nullable();
            $table->string('event_type', 24);
            $table->date('effective_date');
            $table->text('reason');
            $table->foreignUuid('performed_by');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'student_id', 'created_at'], 'student_academic_events_student_timeline_idx');
            $table->index(['tenant_id', 'event_type', 'created_at'], 'student_academic_events_type_timeline_idx');

            $table->foreign(['student_id', 'tenant_id'], 'student_academic_events_student_tenant_fk')
                ->references(['id', 'tenant_id'])->on('student_profiles')->restrictOnDelete();
            $table->foreign(['academic_year_id', 'tenant_id'], 'student_academic_events_year_tenant_fk')
                ->references(['id', 'tenant_id'])->on('academic_years')->restrictOnDelete();
            $table->foreign(['from_rombel_id', 'tenant_id'], 'student_academic_events_from_rombel_tenant_fk')
                ->references(['id', 'tenant_id'])->on('rombels')->restrictOnDelete();
            $table->foreign(['to_rombel_id', 'tenant_id'], 'student_academic_events_to_rombel_tenant_fk')
                ->references(['id', 'tenant_id'])->on('rombels')->restrictOnDelete();
            $table->foreign(['performed_by', 'tenant_id'], 'student_academic_events_actor_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academic_events');
    }
};
