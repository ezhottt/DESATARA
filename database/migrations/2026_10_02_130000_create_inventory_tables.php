<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_sessions', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('period_label', 100);
            $t->jsonb('scope_definition')->default('{}');
            $t->timestampTz('reference_at');
            $t->string('status', 30)->default('draft');
            $t->timestampTz('started_at')->nullable();
            $t->timestampTz('reviewed_at')->nullable();
            $t->timestampTz('finalized_at')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestampsTz();
            $t->unique(['tenant_id', 'id']);
            $t->index(['tenant_id', 'status']);
        });

        Schema::create('inventory_session_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('inventory_session_id');
            $t->unsignedBigInteger('membership_id');
            $t->string('assignment_role', 50);
            $t->timestampTz('assigned_at')->useCurrent();
            $t->foreign(['tenant_id', 'inventory_session_id'])->references(['tenant_id', 'id'])->on('inventory_sessions')->restrictOnDelete();
            $t->foreign(['tenant_id', 'membership_id'])->references(['tenant_id', 'id'])->on('tenant_memberships')->restrictOnDelete();
            $t->unique(['tenant_id', 'inventory_session_id', 'membership_id', 'assignment_role'], 'inventory_assignment_unique');
        });

        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('inventory_session_id');
            $t->unsignedBigInteger('asset_id');
            $t->uuid('asset_uuid_snapshot');
            $t->string('asset_code_snapshot', 150)->nullable();
            $t->string('register_number_snapshot', 150)->nullable();
            $t->string('asset_name_snapshot');
            $t->unsignedBigInteger('classification_id_snapshot');
            $t->string('classification_code_snapshot', 100)->nullable();
            $t->string('classification_name_snapshot')->nullable();
            $t->unsignedBigInteger('expected_location_id')->nullable();
            $t->jsonb('expected_location_snapshot')->nullable();
            $t->unsignedBigInteger('observed_location_id')->nullable();
            $t->jsonb('observed_location_snapshot')->nullable();
            $t->string('expected_condition', 50)->nullable();
            $t->string('observed_condition', 50)->nullable();
            $t->string('lifecycle_status_snapshot', 50);
            $t->string('verification_result', 50)->default('not_checked');
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('verified_at')->nullable();
            $t->jsonb('snapshot_payload')->default('{}');
            $t->timestampsTz();
            $t->foreign(['tenant_id', 'inventory_session_id'])->references(['tenant_id', 'id'])->on('inventory_sessions')->restrictOnDelete();
            $t->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $t->unique(['tenant_id', 'id']);
            $t->unique(['tenant_id', 'inventory_session_id', 'asset_id']);
            $t->index(['tenant_id', 'inventory_session_id']);
        });

        Schema::create('inventory_discoveries', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('inventory_session_id');
            $t->string('temporary_label', 150);
            $t->text('description');
            $t->unsignedBigInteger('observed_location_id')->nullable();
            $t->string('observed_condition', 50)->nullable();
            $t->string('resolution_status', 50)->default('discovered');
            $t->unsignedBigInteger('resolved_asset_id')->nullable();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('resolved_at')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestampsTz();
            $t->foreign(['tenant_id', 'inventory_session_id'])->references(['tenant_id', 'id'])->on('inventory_sessions')->restrictOnDelete();
            $t->foreign(['tenant_id', 'resolved_asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $t->index(['tenant_id', 'inventory_session_id', 'resolution_status']);
        });

        Schema::create('inventory_discrepancies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('inventory_session_id');
            $t->unsignedBigInteger('inventory_item_id')->nullable();
            $t->unsignedBigInteger('asset_id')->nullable();
            $t->string('discrepancy_type', 50);
            $t->text('description')->nullable();
            $t->string('status', 50)->default('open');
            $t->text('proposed_resolution')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('reviewed_at')->nullable();
            $t->timestampsTz();
            $t->foreign(['tenant_id', 'inventory_session_id'])->references(['tenant_id', 'id'])->on('inventory_sessions')->restrictOnDelete();
            $t->foreign(['tenant_id', 'inventory_item_id'])->references(['tenant_id', 'id'])->on('inventory_items')->restrictOnDelete();
            $t->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $t->unique(['tenant_id', 'id']);
            $t->index(['tenant_id', 'inventory_session_id', 'status']);
        });

        Schema::create('inventory_reconciliations', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('inventory_discrepancy_id');
            $t->string('resolution_type', 50);
            $t->foreignId('resolution_subject_type_id')->nullable()->constrained('subject_types')->nullOnDelete();
            $t->unsignedBigInteger('resolution_subject_id')->nullable();
            $t->text('decision_notes');
            $t->foreignId('authority_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('workflow_instance_id')->nullable();
            $t->foreignId('reconciled_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('reconciled_at');
            $t->string('idempotency_key', 150);
            $t->timestampTz('created_at')->useCurrent();
            $t->foreign(['tenant_id', 'inventory_discrepancy_id'])->references(['tenant_id', 'id'])->on('inventory_discrepancies')->restrictOnDelete();
            $t->unique(['tenant_id', 'idempotency_key']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('action', 100);
            $t->string('subject_type', 150);
            $t->unsignedBigInteger('subject_id');
            $t->jsonb('before_state')->nullable();
            $t->jsonb('after_state')->nullable();
            $t->string('correlation_id', 100)->nullable();
            $t->timestampTz('occurred_at');
            $t->index(['tenant_id', 'occurred_at']);
            $t->index(['tenant_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('inventory_reconciliations');
        Schema::dropIfExists('inventory_discrepancies');
        Schema::dropIfExists('inventory_discoveries');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_session_assignments');
        Schema::dropIfExists('inventory_sessions');
    }
};
