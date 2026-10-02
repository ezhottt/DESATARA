<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_memberships', fn (Blueprint $table) => $table->unique(['tenant_id', 'id'], 'tenant_memberships_tenant_id_id_unique'));
    }

    public function down(): void
    {
        Schema::table('tenant_memberships', fn (Blueprint $table) => $table->dropUnique('tenant_memberships_tenant_id_id_unique'));
    }
};
