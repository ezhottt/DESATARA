<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('code', 100);
            $t->string('name', 150);
            $t->string('scope_type', 30);
            $t->boolean('is_system')->default(false);
            $t->timestampsTz();
            $t->unique(['scope_type', 'code']);
        });
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_scope_check CHECK (scope_type IN ('platform','tenant'))");
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('code', 150)->unique();
            $t->string('domain', 100);
            $t->string('action', 100);
            $t->text('description')->nullable();
            $t->timestampsTz();
        });
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });
        Schema::create('membership_roles', function (Blueprint $t) {
            $t->foreignId('membership_id')->constrained('tenant_memberships')->cascadeOnDelete();
            $t->foreignId('role_id')->constrained()->restrictOnDelete();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestampTz('assigned_at');
            $t->primary(['membership_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
