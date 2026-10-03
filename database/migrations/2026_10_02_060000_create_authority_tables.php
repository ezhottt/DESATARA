<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_officials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('membership_id');
            $t->string('official_type', 100);
            $t->string('position_name', 200);
            $t->string('authority_code', 150);
            $t->jsonb('authority_scope')->default('[]');
            $t->string('appointment_number', 150)->nullable();
            $t->date('appointment_date')->nullable();
            $t->date('valid_from');
            $t->date('valid_until')->nullable();
            $t->string('status', 30);
            $t->unsignedBigInteger('evidence_document_id')->nullable();
            $t->timestampsTz();
            $t->foreign(['tenant_id', 'membership_id'])->references(['tenant_id', 'id'])->on('tenant_memberships')->restrictOnDelete();
            $t->index(['tenant_id', 'authority_code', 'status']);
        });
        DB::statement("ALTER TABLE tenant_officials ADD CONSTRAINT tenant_officials_status_check CHECK (status IN ('pending','active','expired','revoked','superseded'))");
        DB::statement('ALTER TABLE tenant_officials ADD CONSTRAINT tenant_officials_validity_check CHECK (valid_until IS NULL OR valid_until >= valid_from)');
        Schema::create('authority_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('membership_id');
            $t->foreignId('tenant_official_id')->nullable()->constrained('tenant_officials')->nullOnDelete();
            $t->string('authority_type', 100);
            $t->string('official_type', 100)->nullable();
            $t->string('position_name', 200)->nullable();
            $t->string('appointment_number', 150)->nullable();
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->jsonb('snapshot_payload');
            $t->timestampTz('captured_at');
            $t->foreign(['tenant_id', 'membership_id'])->references(['tenant_id', 'id'])->on('tenant_memberships')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authority_snapshots');
        Schema::dropIfExists('tenant_officials');
    }
};
