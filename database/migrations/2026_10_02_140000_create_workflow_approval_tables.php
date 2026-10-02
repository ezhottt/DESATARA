<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('status', 30)->default('active');
            $t->timestampsTz();
        });
        Schema::create('workflow_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workflow_definition_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->jsonb('definition')->default('{}');
            $t->date('effective_from')->nullable();
            $t->date('effective_until')->nullable();
            $t->string('status', 30);
            $t->timestampTz('published_at')->nullable();
            $t->timestampsTz();
            $t->unique(['workflow_definition_id', 'version']);
        });
        Schema::create('workflow_instances', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('workflow_version_id')->constrained()->restrictOnDelete();
            $t->foreignId('subject_type_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('subject_id');
            $t->string('current_state', 40);
            $t->foreignId('started_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('started_at');
            $t->timestampTz('completed_at')->nullable();
            $t->timestampTz('cancelled_at')->nullable();
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestampsTz();
            $t->index(['tenant_id', 'subject_type_id', 'subject_id']);
        });
        Schema::create('workflow_transitions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('workflow_instance_id')->constrained()->restrictOnDelete();
            $t->string('from_state', 40)->nullable();
            $t->string('to_state', 40);
            $t->string('action', 40);
            $t->foreignId('actor_membership_id')->constrained('tenant_memberships')->restrictOnDelete();
            $t->foreignId('authority_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $t->text('reason')->nullable();
            $t->string('idempotency_key', 150)->nullable();
            $t->timestampTz('occurred_at');
            $t->unique(['tenant_id', 'idempotency_key']);
        });
        Schema::create('approval_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('workflow_instance_id')->constrained()->restrictOnDelete();
            $t->foreignId('subject_type_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('subject_id');
            $t->string('status', 30);
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('submitted_at');
            $t->timestampTz('completed_at')->nullable();
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestampsTz();
        });
        Schema::create('approval_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('approval_request_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('sequence');
            $t->foreignId('required_permission_id')->nullable()->constrained('permissions')->restrictOnDelete();
            $t->jsonb('authority_requirement')->nullable();
            $t->string('status', 30);
            $t->timestampTz('acted_at')->nullable();
            $t->timestampsTz();
            $t->unique(['tenant_id', 'approval_request_id', 'sequence']);
        });
        Schema::create('approval_actions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('approval_request_id')->constrained()->restrictOnDelete();
            $t->foreignId('approval_step_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_membership_id')->constrained('tenant_memberships')->restrictOnDelete();
            $t->foreignId('authority_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 30);
            $t->text('notes')->nullable();
            $t->string('idempotency_key', 150);
            $t->timestampTz('acted_at');
            $t->unique(['tenant_id', 'idempotency_key']);
        });

        foreach (['asset_classification_assignments', 'asset_mutations', 'asset_responsibility_assignments', 'asset_condition_events', 'asset_lifecycle_events', 'asset_corrections', 'asset_maintenances', 'asset_valuations', 'asset_usage_determinations', 'asset_utilizations', 'asset_transfers', 'asset_disposals', 'inventory_reconciliations', 'external_approvals'] as $table) {
            if (Schema::hasColumn($table, 'workflow_instance_id')) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_workflow_deferred_check");
                Schema::table($table, function (Blueprint $t) {
                    $t->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->restrictOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['asset_classification_assignments', 'asset_mutations', 'asset_responsibility_assignments', 'asset_condition_events', 'asset_lifecycle_events', 'asset_corrections', 'asset_maintenances', 'asset_valuations', 'asset_usage_determinations', 'asset_utilizations', 'asset_transfers', 'asset_disposals', 'inventory_reconciliations', 'external_approvals'] as $table) {
            if (Schema::hasColumn($table, 'workflow_instance_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['workflow_instance_id']);
                });
            }
        }
        foreach (['approval_actions', 'approval_steps', 'approval_requests', 'workflow_transitions', 'workflow_instances', 'workflow_versions', 'workflow_definitions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
