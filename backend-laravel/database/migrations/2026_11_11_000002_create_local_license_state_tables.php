<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('local_installation_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->uuid('installation_id')->unique();
            $table->string('key_id', 80)->unique();
            $table->text('public_key');
            $table->longText('encrypted_private_key');
            $table->char('key_fingerprint', 64);
            $table->string('registered_domain', 190);
            $table->string('product_code', 64);
            $table->string('product_version', 40);
            $table->timestamps();
            $table->unique(['tenant_id', 'product_code']);
        });

        Schema::create('local_license_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('installation_identity_id')->unique()->constrained('local_installation_identities')->restrictOnDelete();
            $table->uuid('license_id')->nullable();
            $table->string('status', 32)->default('NOT_ACTIVATED');
            $table->unsignedBigInteger('license_revision')->default(0);
            $table->unsignedBigInteger('lease_revision')->default(0);
            $table->unsignedSmallInteger('policy_version')->default(1);
            $table->string('trusted_key_id', 80)->nullable();
            $table->text('trusted_public_key')->nullable();
            $table->longText('signed_license_artifact')->nullable();
            $table->longText('signed_lease_artifact')->nullable();
            $table->timestamp('license_expires_at')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamp('last_trusted_server_time')->nullable();
            $table->timestamp('last_observed_at')->nullable();
            $table->timestamp('last_publisher_check_at')->nullable();
            $table->string('last_error_code', 80)->nullable();
            $table->timestamps();
            $table->index(['status', 'lease_expires_at']);
        });

        Schema::create('local_activation_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('installation_identity_id')->constrained('local_installation_identities')->restrictOnDelete();
            $table->uuid('challenge_id')->unique();
            $table->longText('encrypted_challenge');
            $table->longText('encrypted_proof_jws');
            $table->string('status', 24)->default('PENDING');
            $table->timestamp('challenge_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['installation_identity_id', 'status']);
        });

        Schema::create('local_license_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('installation_identity_id')->constrained('local_installation_identities')->restrictOnDelete();
            $table->string('event_type', 64);
            $table->string('reason_code', 80)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['installation_identity_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['local_license_events', 'local_activation_attempts', 'local_license_states', 'local_installation_identities'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
