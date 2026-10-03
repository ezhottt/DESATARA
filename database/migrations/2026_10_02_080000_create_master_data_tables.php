<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classification_schemes', function (Blueprint $t) {
            $t->id();
            $t->string('code', 100)->unique();
            $t->string('name');
            $t->string('scope', 50);
            $t->string('status', 30);
            $t->timestampsTz();
        });
        Schema::create('classification_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('classification_scheme_id')->constrained()->restrictOnDelete();
            $t->string('version_label', 100);
            $t->date('effective_from');
            $t->date('effective_until')->nullable();
            $t->string('status', 30);
            $t->foreignId('regulation_version_id')->nullable()->constrained()->nullOnDelete();
            $t->timestampsTz();
            $t->unique(['classification_scheme_id', 'version_label']);
        });
        Schema::create('asset_classifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('classification_version_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->string('code', 100);
            $t->string('name');
            $t->smallInteger('level');
            $t->string('status', 30);
            $t->timestampsTz();
            $t->unique(['classification_version_id', 'code']);
            $t->unique(['classification_version_id', 'id']);
            $t->foreign(['classification_version_id', 'parent_id'])->references(['classification_version_id', 'id'])->on('asset_classifications')->restrictOnDelete();
        });
        Schema::create('funding_sources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('code', 100);
            $t->string('name');
            $t->string('status', 30);
            $t->timestampsTz();
            $t->unique(['tenant_id', 'code']);
        });
        Schema::create('units', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->string('symbol', 30)->nullable();
            $t->string('status', 30);
            $t->timestampsTz();
        });
        foreach (['organizational_units', 'asset_locations'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $t->unsignedBigInteger('parent_id')->nullable();
                $t->string('code', 100);
                $t->string('name');
                $t->string($table === 'organizational_units' ? 'type' : 'location_type', 50);
                $t->string('status', 30);
                if ($table === 'organizational_units') {
                    $t->date('valid_from')->nullable();
                    $t->date('valid_until')->nullable();
                }$t->timestampsTz();
                $t->unique(['tenant_id', 'code']);
                $t->unique(['tenant_id', 'id']);
                $t->foreign(['tenant_id', 'parent_id'])->references(['tenant_id', 'id'])->on($table)->restrictOnDelete();
            });
        }
        Schema::create('responsible_parties', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('party_type', 40);
            $t->unsignedBigInteger('membership_id')->nullable();
            $t->unsignedBigInteger('organizational_unit_id')->nullable();
            $t->string('status', 30);
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->timestampsTz();
            $t->unique(['tenant_id', 'id']);
            $t->foreign(['tenant_id', 'membership_id'])->references(['tenant_id', 'id'])->on('tenant_memberships')->restrictOnDelete();
            $t->foreign(['tenant_id', 'organizational_unit_id'])->references(['tenant_id', 'id'])->on('organizational_units')->restrictOnDelete();
        });
        Schema::create('numbering_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('sequence_type', 100);
            $table->string('period_key', 100);
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestampTz('updated_at')->nullable();
            $table->unique(['tenant_id', 'sequence_type', 'period_key']);
        });

        DB::statement("ALTER TABLE responsible_parties ADD CONSTRAINT responsible_parties_type_check CHECK ((party_type='membership' AND membership_id IS NOT NULL AND organizational_unit_id IS NULL) OR (party_type='organizational_unit' AND organizational_unit_id IS NOT NULL AND membership_id IS NULL))");
    }

    public function down(): void
    {
        foreach (['numbering_sequences', 'responsible_parties', 'asset_locations', 'organizational_units', 'units', 'funding_sources', 'asset_classifications', 'classification_versions', 'classification_schemes'] as $x) {
            Schema::dropIfExists($x);
        }
    }
};
