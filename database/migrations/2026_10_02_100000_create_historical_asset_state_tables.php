<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->index(['tenant_id', 'current_location_id'], 'assets_tenant_current_location_index');
            $table->index(['tenant_id', 'current_responsible_party_id'], 'assets_tenant_current_responsibility_index');
            $table->index(['tenant_id', 'acquisition_year'], 'assets_tenant_acquisition_year_index');
        });

        Schema::create('asset_classification_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->foreignId('classification_id')->constrained('asset_classifications')->restrictOnDelete();
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until')->nullable();
            $table->string('assignment_type', 50);
            $table->text('reason')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'valid_from'], 'asset_classification_assignments_history_index');
        });
        DB::statement('ALTER TABLE asset_classification_assignments ADD CONSTRAINT asset_classification_assignments_dates_check CHECK (valid_until IS NULL OR valid_until >= valid_from)');
        DB::statement('CREATE UNIQUE INDEX asset_classification_assignments_open_unique ON asset_classification_assignments (tenant_id, asset_id) WHERE valid_until IS NULL');

        Schema::create('asset_mutations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('origin_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id');
            $table->string('mutation_type', 100);
            $table->text('reason')->nullable();
            $table->timestampTz('effective_at');
            $table->string('status', 50);
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('executed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->timestampsTz();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->foreign(['tenant_id', 'origin_location_id'])->references(['tenant_id', 'id'])->on('asset_locations')->restrictOnDelete();
            $table->foreign(['tenant_id', 'destination_location_id'])->references(['tenant_id', 'id'])->on('asset_locations')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'effective_at']);
        });
        DB::statement('CREATE UNIQUE INDEX asset_mutations_idempotency_unique ON asset_mutations (tenant_id, idempotency_key) WHERE idempotency_key IS NOT NULL');

        Schema::create('asset_responsibility_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('responsible_party_id');
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until')->nullable();
            $table->string('assignment_reference')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->foreign(['tenant_id', 'responsible_party_id'])->references(['tenant_id', 'id'])->on('responsible_parties')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'valid_from'], 'asset_responsibility_assignments_history_index');
        });
        DB::statement('ALTER TABLE asset_responsibility_assignments ADD CONSTRAINT asset_responsibility_assignments_dates_check CHECK (valid_until IS NULL OR valid_until >= valid_from)');
        DB::statement('CREATE UNIQUE INDEX asset_responsibility_assignments_open_unique ON asset_responsibility_assignments (tenant_id, asset_id) WHERE valid_until IS NULL');

        Schema::create('asset_condition_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->string('previous_condition', 50)->nullable();
            $table->string('new_condition', 50);
            $table->timestampTz('effective_at');
            $table->text('reason')->nullable();
            $table->string('source_type', 50);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'effective_at']);
        });
        DB::statement('CREATE UNIQUE INDEX asset_condition_events_idempotency_unique ON asset_condition_events (tenant_id, idempotency_key) WHERE idempotency_key IS NOT NULL');

        Schema::create('asset_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->string('transition_type', 100);
            $table->timestampTz('effective_at');
            $table->text('reason')->nullable();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'effective_at']);
        });
        DB::statement('CREATE UNIQUE INDEX asset_lifecycle_events_idempotency_unique ON asset_lifecycle_events (tenant_id, idempotency_key) WHERE idempotency_key IS NOT NULL');

        Schema::create('asset_corrections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->string('correction_type', 100);
            $table->jsonb('corrected_fields');
            $table->jsonb('before_values');
            $table->jsonb('after_values');
            $table->text('reason');
            $table->string('reference')->nullable();
            $table->foreignId('applied_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('applied_at');
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'applied_at']);
        });
        DB::statement("ALTER TABLE asset_corrections ADD CONSTRAINT asset_corrections_json_objects_check CHECK (jsonb_typeof(corrected_fields) = 'object' AND jsonb_typeof(before_values) = 'object' AND jsonb_typeof(after_values) = 'object')");
        DB::statement('CREATE UNIQUE INDEX asset_corrections_idempotency_unique ON asset_corrections (tenant_id, idempotency_key) WHERE idempotency_key IS NOT NULL');

        foreach (['asset_classification_assignments', 'asset_mutations', 'asset_responsibility_assignments', 'asset_condition_events', 'asset_lifecycle_events', 'asset_corrections'] as $table) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_workflow_deferred_check CHECK (workflow_instance_id IS NULL)");
        }

        $this->createHistoryGuards();
        $this->backfillExistingAssetHistory();
        $this->createRegistrationHistoryTrigger();
    }

    private function backfillExistingAssetHistory(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO asset_classification_assignments
                (tenant_id, asset_id, classification_id, valid_from, assignment_type, assigned_by, created_at)
            SELECT tenant_id, id, classification_id, created_at, 'initial', created_by, created_at
            FROM assets
        SQL);
        DB::statement(<<<'SQL'
            INSERT INTO asset_mutations
                (uuid, tenant_id, asset_id, origin_location_id, destination_location_id, mutation_type, effective_at, status, requested_by, executed_by, created_at, updated_at)
            SELECT gen_random_uuid(), tenant_id, id, NULL, current_location_id, 'initial_placement', created_at, 'completed', created_by, created_by, created_at, created_at
            FROM assets
            WHERE current_location_id IS NOT NULL
        SQL);
        DB::statement(<<<'SQL'
            INSERT INTO asset_responsibility_assignments
                (tenant_id, asset_id, responsible_party_id, valid_from, assigned_by, created_at)
            SELECT tenant_id, id, current_responsible_party_id, created_at, created_by, created_at
            FROM assets
            WHERE current_responsible_party_id IS NOT NULL
        SQL);
        DB::statement(<<<'SQL'
            INSERT INTO asset_condition_events
                (uuid, tenant_id, asset_id, previous_condition, new_condition, effective_at, source_type, actor_id, created_at)
            SELECT gen_random_uuid(), tenant_id, id, NULL, condition, created_at, 'registration', created_by, created_at
            FROM assets
        SQL);
        DB::statement(<<<'SQL'
            INSERT INTO asset_lifecycle_events
                (uuid, tenant_id, asset_id, from_status, to_status, transition_type, effective_at, actor_id, created_at)
            SELECT gen_random_uuid(), tenant_id, id, NULL, lifecycle_status, 'registration', created_at, created_by, created_at
            FROM assets
        SQL);
    }

    private function createHistoryGuards(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION desatara_guard_asset_projection() RETURNS trigger AS $$
            BEGIN
                IF (
                    NEW.classification_id IS DISTINCT FROM OLD.classification_id OR
                    NEW.current_location_id IS DISTINCT FROM OLD.current_location_id OR
                    NEW.current_responsible_party_id IS DISTINCT FROM OLD.current_responsible_party_id OR
                    NEW.condition IS DISTINCT FROM OLD.condition OR
                    NEW.lifecycle_status IS DISTINCT FROM OLD.lifecycle_status OR
                    NEW.lock_version IS DISTINCT FROM OLD.lock_version
                ) AND current_setting('desatara.asset_history_write', true) IS DISTINCT FROM 'on' THEN
                    RAISE EXCEPTION 'asset historical projection must use the domain action' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER assets_historical_projection_guard
            BEFORE UPDATE ON assets
            FOR EACH ROW EXECUTE FUNCTION desatara_guard_asset_projection();

            CREATE OR REPLACE FUNCTION desatara_prevent_history_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'historical rows are immutable' USING ERRCODE = '23514';
            END;
            $$ LANGUAGE plpgsql;

            CREATE OR REPLACE FUNCTION desatara_guard_interval_history() RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'DELETE' OR OLD.valid_until IS NOT NULL OR NEW.valid_until IS NULL OR
                   (to_jsonb(NEW) - 'valid_until') IS DISTINCT FROM (to_jsonb(OLD) - 'valid_until') THEN
                    RAISE EXCEPTION 'historical assignment is immutable except when closing its open interval' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE OR REPLACE FUNCTION desatara_guard_asset_mutation_history() RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'DELETE' OR OLD.status = 'completed' THEN
                    RAISE EXCEPTION 'executed asset mutation is immutable' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER asset_classification_assignments_immutable
            BEFORE UPDATE OR DELETE ON asset_classification_assignments
            FOR EACH ROW EXECUTE FUNCTION desatara_guard_interval_history();

            CREATE TRIGGER asset_responsibility_assignments_immutable
            BEFORE UPDATE OR DELETE ON asset_responsibility_assignments
            FOR EACH ROW EXECUTE FUNCTION desatara_guard_interval_history();

            CREATE TRIGGER asset_mutations_immutable
            BEFORE UPDATE OR DELETE ON asset_mutations
            FOR EACH ROW EXECUTE FUNCTION desatara_guard_asset_mutation_history();

            CREATE TRIGGER asset_condition_events_immutable
            BEFORE UPDATE OR DELETE ON asset_condition_events
            FOR EACH ROW EXECUTE FUNCTION desatara_prevent_history_mutation();

            CREATE TRIGGER asset_lifecycle_events_immutable
            BEFORE UPDATE OR DELETE ON asset_lifecycle_events
            FOR EACH ROW EXECUTE FUNCTION desatara_prevent_history_mutation();

            CREATE TRIGGER asset_corrections_immutable
            BEFORE UPDATE OR DELETE ON asset_corrections
            FOR EACH ROW EXECUTE FUNCTION desatara_prevent_history_mutation();
        SQL);
    }

    private function createRegistrationHistoryTrigger(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION desatara_initialize_asset_history() RETURNS trigger AS $$
            DECLARE
                occurred_at timestamptz := COALESCE(NEW.created_at, clock_timestamp());
            BEGIN
                INSERT INTO asset_classification_assignments
                    (tenant_id, asset_id, classification_id, valid_from, assignment_type, assigned_by, created_at)
                VALUES
                    (NEW.tenant_id, NEW.id, NEW.classification_id, occurred_at, 'initial', NEW.created_by, occurred_at);

                IF NEW.current_location_id IS NOT NULL THEN
                    INSERT INTO asset_mutations
                        (uuid, tenant_id, asset_id, origin_location_id, destination_location_id, mutation_type, effective_at, status, requested_by, executed_by, created_at, updated_at)
                    VALUES
                        (gen_random_uuid(), NEW.tenant_id, NEW.id, NULL, NEW.current_location_id, 'initial_placement', occurred_at, 'completed', NEW.created_by, NEW.created_by, occurred_at, occurred_at);
                END IF;

                IF NEW.current_responsible_party_id IS NOT NULL THEN
                    INSERT INTO asset_responsibility_assignments
                        (tenant_id, asset_id, responsible_party_id, valid_from, assigned_by, created_at)
                    VALUES
                        (NEW.tenant_id, NEW.id, NEW.current_responsible_party_id, occurred_at, NEW.created_by, occurred_at);
                END IF;

                INSERT INTO asset_condition_events
                    (uuid, tenant_id, asset_id, previous_condition, new_condition, effective_at, source_type, actor_id, created_at)
                VALUES
                    (gen_random_uuid(), NEW.tenant_id, NEW.id, NULL, NEW.condition, occurred_at, 'registration', NEW.created_by, occurred_at);

                INSERT INTO asset_lifecycle_events
                    (uuid, tenant_id, asset_id, from_status, to_status, transition_type, effective_at, actor_id, created_at)
                VALUES
                    (gen_random_uuid(), NEW.tenant_id, NEW.id, NULL, NEW.lifecycle_status, 'registration', occurred_at, NEW.created_by, occurred_at);

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER assets_initialize_history
            AFTER INSERT ON assets
            FOR EACH ROW EXECUTE FUNCTION desatara_initialize_asset_history();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS assets_initialize_history ON assets');
        DB::statement('DROP TRIGGER IF EXISTS assets_historical_projection_guard ON assets');
        DB::statement('DROP FUNCTION IF EXISTS desatara_initialize_asset_history()');
        DB::statement('DROP FUNCTION IF EXISTS desatara_guard_asset_projection()');

        foreach (['asset_corrections', 'asset_lifecycle_events', 'asset_condition_events', 'asset_responsibility_assignments', 'asset_mutations', 'asset_classification_assignments'] as $table) {
            Schema::dropIfExists($table);
        }

        DB::statement('DROP FUNCTION IF EXISTS desatara_guard_asset_mutation_history()');
        DB::statement('DROP FUNCTION IF EXISTS desatara_guard_interval_history()');
        DB::statement('DROP FUNCTION IF EXISTS desatara_prevent_history_mutation()');

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('assets_tenant_current_location_index');
            $table->dropIndex('assets_tenant_current_responsibility_index');
            $table->dropIndex('assets_tenant_acquisition_year_index');
        });
    }
};
