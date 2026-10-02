<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status', 30);
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('valid_from')->nullable();
            $table->timestampTz('valid_until')->nullable();
            $table->timestampsTz();
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
        });
        DB::statement("ALTER TABLE tenant_memberships ADD CONSTRAINT tenant_memberships_status_check CHECK (status IN ('invited','active','suspended','expired','revoked'))");
        DB::statement('ALTER TABLE tenant_memberships ADD CONSTRAINT tenant_memberships_validity_check CHECK (valid_until IS NULL OR valid_from IS NULL OR valid_until >= valid_from)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_memberships');
    }
};
