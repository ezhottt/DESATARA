<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulations', fn (Blueprint $t) => [$t->id(), $t->string('code', 100)->unique(), $t->text('title'), $t->string('jurisdiction_level', 50), $t->string('issuing_authority', 200)->nullable(), $t->timestampsTz()]);
        Schema::create('regulation_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('regulation_id')->constrained()->restrictOnDelete();
            $t->string('version_label', 100);
            $t->date('effective_from');
            $t->date('effective_until')->nullable();
            $t->string('status', 30);
            $t->text('source_reference');
            $t->string('source_checksum', 128)->nullable();
            $t->timestampsTz();
            $t->unique(['regulation_id', 'version_label']);
        });
        DB::statement('ALTER TABLE regulation_versions ADD CONSTRAINT regulation_versions_dates_check CHECK (effective_until IS NULL OR effective_until >= effective_from)');
        Schema::create('regulation_provisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('regulation_version_id')->constrained()->restrictOnDelete();
            $t->string('provision_type', 50);
            $t->string('provision_number', 100);
            $t->string('heading')->nullable();
            $t->text('content_reference')->nullable();
            $t->timestampsTz();
            $t->unique(['regulation_version_id', 'provision_type', 'provision_number']);
        });
        Schema::create('business_rules', function (Blueprint $t) {
            $t->id();
            $t->string('code', 100)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->date('effective_from')->nullable();
            $t->date('effective_until')->nullable();
            $t->string('status', 30);
            $t->string('implementation_status', 30);
            $t->boolean('is_mandatory')->default(false);
            $t->string('jurisdiction_level', 50)->default('national');
            $t->timestampsTz();
        });
        Schema::create('business_rule_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_rule_id')->constrained()->restrictOnDelete();
            $t->string('version_label', 100);
            $t->jsonb('rule_payload')->default('{}');
            $t->string('status', 30);
            $t->date('effective_from')->nullable();
            $t->date('effective_until')->nullable();
            $t->timestampsTz();
            $t->unique(['business_rule_id', 'version_label']);
        });
        Schema::create('business_rule_provisions', function (Blueprint $t) {
            $t->foreignId('business_rule_id')->constrained()->cascadeOnDelete();
            $t->foreignId('regulation_provision_id')->constrained()->restrictOnDelete();
            $t->primary(['business_rule_id', 'regulation_provision_id']);
        });
        Schema::create('regulatory_bindings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_rule_id')->constrained()->cascadeOnDelete();
            $t->string('binding_type', 30);
            $t->string('binding_key', 200);
            $t->date('effective_from')->nullable();
            $t->date('effective_until')->nullable();
            $t->jsonb('metadata')->default('{}');
            $t->timestampsTz();
            $t->unique(['business_rule_id', 'binding_type', 'binding_key']);
        });
        DB::statement("ALTER TABLE regulatory_bindings ADD CONSTRAINT regulatory_bindings_type_check CHECK (binding_type IN ('workflow','report','classification','domain_contract'))");
        Schema::create('tenant_regulatory_overlays', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('business_rule_id')->constrained()->restrictOnDelete();
            $t->string('mode', 40);
            $t->jsonb('configuration')->default('{}');
            $t->string('status', 30)->default('active');
            $t->timestampsTz();
            $t->unique(['tenant_id', 'business_rule_id', 'mode']);
        });
        DB::statement("ALTER TABLE tenant_regulatory_overlays ADD CONSTRAINT tenant_overlay_mode_check CHECK (mode IN ('additional_constraint','local_reference'))");
    }

    public function down(): void
    {
        foreach (['tenant_regulatory_overlays', 'regulatory_bindings', 'business_rule_provisions', 'business_rule_versions', 'business_rules', 'regulation_provisions', 'regulation_versions', 'regulations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
