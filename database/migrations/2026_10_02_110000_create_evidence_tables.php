<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->string('model_key', 150);
            $table->string('status', 30)->default('active');
            $table->timestampsTz();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 100);
            $table->string('document_number')->nullable();
            $table->date('document_date')->nullable();
            $table->string('storage_disk', 50)->default('private');
            $table->string('storage_path');
            $table->string('original_filename');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 64);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('malware_scan_status', 30)->default('not_available');
            $table->string('storage_state', 30)->default('stored');
            $table->string('visibility', 30)->default('private');
            $table->string('classification', 30)->default('sensitive');
            $table->foreignId('supersedes_document_id')->nullable()->constrained('documents')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'checksum']);
            $table->index(['tenant_id', 'visibility', 'storage_state']);
        });

        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('document_id');
            $table->foreignId('subject_type_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('subject_id');
            $table->string('purpose', 100);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'document_id'])->references(['tenant_id', 'id'])->on('documents')->cascadeOnDelete();
            $table->index(['tenant_id', 'subject_type_id', 'subject_id']);
            $table->index(['tenant_id', 'document_id']);
        });

        Schema::create('asset_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('document_id');
            $table->string('category', 100);
            $table->timestampTz('captured_at')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'document_id'])->references(['tenant_id', 'id'])->on('documents')->cascadeOnDelete();
        });

        Schema::create('asset_qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->char('token_hash', 64)->unique();
            $table->string('status', 30)->default('active');
            $table->timestampTz('issued_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->unsignedBigInteger('rotated_from_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreign(['tenant_id', 'asset_id'])->references(['tenant_id', 'id'])->on('assets')->cascadeOnDelete();
            $table->foreign('rotated_from_id')->references('id')->on('asset_qr_tokens')->restrictOnDelete();
            $table->index(['tenant_id', 'asset_id', 'status']);
        });
        DB::statement("CREATE UNIQUE INDEX asset_qr_tokens_one_active_per_asset ON asset_qr_tokens (tenant_id, asset_id) WHERE status = 'active'");

        DB::table('subject_types')->insert(['code' => 'asset', 'name' => 'Asset', 'model_key' => 'asset', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_qr_tokens');
        Schema::dropIfExists('asset_photos');
        Schema::dropIfExists('document_links');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('subject_types');
    }
};
