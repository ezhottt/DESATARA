<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('village_code', 50)->nullable();
            $table->string('name', 200);
            $table->string('province', 150);
            $table->string('regency', 150);
            $table->string('district', 150);
            $table->text('address')->nullable();
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('locale', 20)->default('id');
            $table->string('status', 30)->default('pending');
            $table->timestampTz('activated_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE tenants ADD CONSTRAINT tenants_status_check CHECK (status IN ('pending','active','suspended','archived'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
