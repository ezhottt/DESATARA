<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('classification_id')->constrained('asset_classifications')->restrictOnDelete();
            $t->string('asset_code', 150)->nullable();
            $t->string('register_number', 150)->nullable();
            $t->string('name');
            $t->text('description')->nullable();
            $t->date('acquisition_date')->nullable();
            $t->smallInteger('acquisition_year')->nullable();
            $t->string('acquisition_origin', 100)->nullable();
            $t->foreignId('funding_source_id')->nullable()->constrained()->restrictOnDelete();
            $t->decimal('quantity', 19, 4)->default(1);
            $t->foreignId('unit_id')->constrained()->restrictOnDelete();
            $t->decimal('unit_price', 19, 2)->nullable();
            $t->decimal('acquisition_value', 19, 2)->nullable();
            $t->unsignedBigInteger('current_location_id')->nullable();
            $t->unsignedBigInteger('current_responsible_party_id')->nullable();
            $t->string('condition', 50);
            $t->string('lifecycle_status', 50)->default('draft');
            $t->string('verification_status', 50)->default('unverified');
            $t->unsignedInteger('lock_version')->default(1);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $t->timestampsTz();
            $t->unique(['tenant_id', 'id']);
            $t->foreign(['tenant_id', 'current_location_id'])->references(['tenant_id', 'id'])->on('asset_locations')->restrictOnDelete();
            $t->foreign(['tenant_id', 'current_responsible_party_id'])->references(['tenant_id', 'id'])->on('responsible_parties')->restrictOnDelete();
            $t->index(['tenant_id', 'lifecycle_status']);
            $t->index(['tenant_id', 'classification_id']);
        });
        DB::statement('ALTER TABLE assets ADD CONSTRAINT assets_values_check CHECK (quantity > 0 AND (acquisition_year IS NULL OR acquisition_year BETWEEN 1900 AND 9999) AND (unit_price IS NULL OR unit_price >= 0) AND (acquisition_value IS NULL OR acquisition_value >= 0) AND lock_version > 0)');
        Schema::create('asset_acquisitions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('asset_id');
            $t->string('acquisition_type', 100);
            $t->date('acquisition_date')->nullable();
            $t->string('source', 150);
            $t->foreignId('funding_source_id')->nullable()->constrained()->restrictOnDelete();
            $t->decimal('quantity', 19, 4);
            $t->decimal('unit_value', 19, 2)->nullable();
            $t->decimal('total_value', 19, 2)->nullable();
            $t->string('counterparty')->nullable();
            $t->string('reference_number')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('created_at')->useCurrent();
            $t->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->restrictOnDelete();
        });
        foreach (['land', 'building', 'vehicle', 'equipment'] as $kind) {
            Schema::create("asset_{$kind}_details", function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $t->unsignedBigInteger('asset_id');
                $t->jsonb('details')->default('{}');
                $t->timestampsTz();
                $t->unique(['tenant_id', 'asset_id']);
                $t->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['asset_equipment_details', 'asset_vehicle_details', 'asset_building_details', 'asset_land_details', 'asset_acquisitions', 'assets'] as $x) {
            Schema::dropIfExists($x);
        }
    }
};
