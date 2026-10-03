<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->string('import_type', 50);
            $t->string('status', 30);
            $t->string('strategy', 20)->default('atomic');
            $t->jsonb('mapping_config')->default('{}');
            $t->unsignedInteger('total_rows')->default(0);
            $t->unsignedInteger('valid_rows')->default(0);
            $t->unsignedInteger('invalid_rows')->default(0);
            $t->unsignedInteger('imported_rows')->default(0);
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->timestampsTz();
            $t->unique(['tenant_id', 'id']);
        });
        Schema::create('import_rows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('import_job_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('row_number');
            $t->jsonb('raw_payload');
            $t->jsonb('normalized_payload')->nullable();
            $t->string('status', 20);
            $t->string('created_resource_type')->nullable();
            $t->unsignedBigInteger('created_resource_id')->nullable();
            $t->timestampsTz();
            $t->unique(['tenant_id', 'import_job_id', 'row_number']);
        });
        Schema::create('import_errors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('import_job_id')->constrained()->cascadeOnDelete();
            $t->foreignId('import_row_id')->constrained()->cascadeOnDelete();
            $t->string('field_name')->nullable();
            $t->string('error_code');
            $t->text('message');
            $t->timestampsTz();
        });
        Schema::create('integration_exports', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->string('target_system', 100);
            $t->string('format_version', 30);
            $t->string('status', 30);
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('generated_document_id')->nullable()->constrained('documents')->restrictOnDelete();
            $t->char('checksum', 64)->nullable();
            $t->jsonb('metadata')->nullable();
            $t->timestampTz('generated_at')->nullable();
            $t->timestampTz('exported_at')->nullable();
            $t->timestampTz('reconciled_at')->nullable();
            $t->timestampsTz();
            $t->unique(['tenant_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_exports');
        Schema::dropIfExists('import_errors');
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_jobs');
    }
};
