<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_support_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('platform_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->jsonb('scope');
            $table->text('reason');
            $table->foreignId('granted_by_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until');
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->uuid('audit_correlation_id');
            $table->timestampsTz();
            $table->index(['tenant_id', 'platform_user_id']);
        });
        DB::statement('ALTER TABLE platform_support_grants ADD CONSTRAINT platform_support_grants_validity_check CHECK (valid_until >= valid_from)');
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_support_grants');
    }
};
