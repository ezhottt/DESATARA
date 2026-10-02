<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->string('maintenance_type', 100);
            $table->date('planned_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->string('vendor')->nullable();
            $table->decimal('planned_cost', 20, 2)->nullable();
            $table->decimal('actual_cost', 20, 2)->nullable();
            $table->foreignId('funding_source_id')->nullable()->constrained('funding_sources')->nullOnDelete();
            $table->string('condition_before', 50)->nullable();
            $table->string('condition_after', 50)->nullable();
            $table->string('status', 50);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'status']);
        });
        DB::statement('ALTER TABLE asset_maintenances ADD CONSTRAINT asset_maintenances_cost_check CHECK ((planned_cost IS NULL OR planned_cost >= 0) AND (actual_cost IS NULL OR actual_cost >= 0))');
        DB::statement('ALTER TABLE asset_maintenances ADD CONSTRAINT asset_maintenances_dates_check CHECK (completed_at IS NULL OR started_at IS NULL OR completed_at >= started_at)');

        Schema::create('asset_valuations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->string('purpose', 100);
            $table->date('valuation_date');
            $table->decimal('amount', 20, 2);
            $table->string('valuer_name');
            $table->string('valuer_reference')->nullable();
            $table->foreignId('authority_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->string('status', 50);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'valuation_date']);
        });
        DB::statement('ALTER TABLE asset_valuations ADD CONSTRAINT asset_valuations_amount_check CHECK (amount >= 0)');

        Schema::create('asset_usage_determinations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->string('decision_number', 100);
            $table->date('decision_date');
            $table->string('status', 50);
            $table->foreignId('authority_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->timestampTz('finalized_at')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'period_year']);
            $table->unique(['tenant_id', 'id']);
        });
        Schema::create('asset_usage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('usage_determination_id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('responsible_party_id')->nullable();
            $table->string('usage_purpose', 150);
            $table->jsonb('snapshot_payload');
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'usage_determination_id'])->references(['tenant_id', 'id'])->on('asset_usage_determinations')->restrictOnDelete();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->foreign(['tenant_id', 'responsible_party_id'])->references(['tenant_id', 'id'])->on('responsible_parties')->nullOnDelete();
            $table->unique(['tenant_id', 'usage_determination_id', 'asset_id']);
        });

        Schema::create('external_approvals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->foreignId('subject_type_id')->constrained('subject_types')->restrictOnDelete();
            $table->unsignedBigInteger('subject_id');
            $table->string('authority_organization');
            $table->string('decision_type', 100);
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('decision_number')->nullable();
            $table->date('decision_date');
            $table->string('status', 50);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['tenant_id', 'subject_type_id', 'subject_id']);
        });

        foreach (['asset_utilizations', 'asset_safeguards'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName) {
                $table->id();
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
                $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
                $table->unsignedBigInteger('asset_id');
                if ($tableName === 'asset_utilizations') {
                    $table->string('utilization_type', 100);
                    $table->string('counterparty')->nullable();
                    $table->timestampTz('start_at');
                    $table->timestampTz('end_at')->nullable();
                    $table->decimal('amount', 20, 2)->nullable();
                    $table->string('status', 50);
                    $table->unsignedBigInteger('workflow_instance_id')->nullable();
                    $table->unsignedInteger('lock_version')->default(1);
                } else {
                    $table->string('safeguard_category', 100);
                    $table->text('finding')->nullable();
                    $table->text('action')->nullable();
                    $table->string('verification_status', 50);
                    $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                    $table->timestampTz('verified_at')->nullable();
                    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                }
                $table->timestampsTz();
                $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
                $table->index(['tenant_id', 'asset_id']);
            });
        }

        foreach (['transfer', 'disposal'] as $kind) {
            $header = "asset_{$kind}s";
            $items = "asset_{$kind}_items";
            Schema::create($header, function (Blueprint $table) use ($kind) {
                $table->id();
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
                $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
                $table->string("{$kind}_type", 100);
                if ($kind === 'disposal') {
                    $table->text('reason');
                }
                $table->date('request_date');
                $table->string('status', 50);
                $table->unsignedBigInteger('workflow_instance_id')->nullable();
                $table->foreignId('authority_snapshot_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('formal_decision_document_id')->nullable()->constrained('documents')->nullOnDelete();
                $table->timestampTz('executed_at')->nullable();
                $table->unsignedInteger('lock_version')->default(1);
                $table->string('idempotency_key', 150)->nullable();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestampsTz();
                $table->unique(['tenant_id', 'idempotency_key']);
                $table->unique(['tenant_id', 'id']);
            });
            $column = "asset_{$kind}_id";
            Schema::create($items, function (Blueprint $table) use ($header, $column) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
                $table->unsignedBigInteger($column);
                $table->unsignedBigInteger('asset_id');
                $table->jsonb('snapshot_payload');
                $table->timestampsTz();
                $table->foreign(['tenant_id', $column])->references(['tenant_id', 'id'])->on($header)->restrictOnDelete();
                $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
                $table->unique(['tenant_id', $column, 'asset_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['transfer', 'disposal'] as $kind) {
            Schema::dropIfExists("asset_{$kind}_items");
            Schema::dropIfExists("asset_{$kind}s");
        }
        Schema::dropIfExists('external_approvals');
        Schema::dropIfExists('asset_usage_items');
        Schema::dropIfExists('asset_usage_determinations');
        Schema::dropIfExists('asset_safeguards');
        Schema::dropIfExists('asset_utilizations');
        Schema::dropIfExists('asset_valuations');
        Schema::dropIfExists('asset_maintenances');
        Schema::dropIfExists('external_approvals');
        Schema::dropIfExists('asset_usage_items');
        Schema::dropIfExists('asset_usage_determinations');
    }
};
