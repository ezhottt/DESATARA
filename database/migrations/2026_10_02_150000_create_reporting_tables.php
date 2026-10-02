<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->smallInteger('year');
            $t->string('period_type', 30);
            $t->unsignedSmallInteger('period_number')->nullable();
            $t->date('start_date');
            $t->date('end_date');
            $t->date('deadline')->nullable();
            $t->string('status', 30)->default('preparation');
            $t->timestampsTz();
            $t->unique(['tenant_id', 'id']);
        });
        DB::statement("ALTER TABLE reporting_periods ADD CONSTRAINT reporting_periods_dates_check CHECK (end_date >= start_date AND year BETWEEN 1900 AND 9999 AND (period_type <> 'semester' OR period_number IN (1, 2)))");
        DB::statement('CREATE UNIQUE INDEX reporting_periods_identity_unique ON reporting_periods (tenant_id, year, period_type, COALESCE(period_number, 0))');

        Schema::create('report_templates', function (Blueprint $t) {
            $t->id();
            $t->string('code', 100)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('status', 30)->default('active');
            $t->timestampsTz();
        });
        Schema::create('report_template_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_template_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->jsonb('regulatory_context')->default('{}');
            $t->jsonb('schema_definition')->default('{}');
            $t->date('effective_from')->nullable();
            $t->date('effective_until')->nullable();
            $t->string('status', 30);
            $t->timestampTz('published_at')->nullable();
            $t->timestampsTz();
            $t->unique(['report_template_id', 'version']);
        });

        Schema::create('asset_reports', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('reporting_period_id')->constrained()->restrictOnDelete();
            $t->foreignId('report_template_version_id')->constrained()->restrictOnDelete();
            $t->string('status', 30);
            $t->unsignedInteger('revision_number')->default(1);
            $t->unsignedBigInteger('parent_report_id')->nullable();
            $t->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('finalized_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('workflow_instance_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestampTz('generated_at')->nullable();
            $t->timestampTz('reviewed_at')->nullable();
            $t->timestampTz('finalized_at')->nullable();
            $t->timestampsTz();
            $t->unique(['tenant_id', 'reporting_period_id', 'report_template_version_id', 'revision_number']);
            $t->unique(['tenant_id', 'id']);
            $t->foreign(['tenant_id', 'reporting_period_id'])->references(['tenant_id', 'id'])->on('reporting_periods')->restrictOnDelete();
            $t->foreign(['tenant_id', 'parent_report_id'])->references(['tenant_id', 'id'])->on('asset_reports')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE asset_reports ADD CONSTRAINT asset_reports_status_check CHECK (status IN ('draft', 'review', 'finalized', 'revision'))");

        Schema::create('report_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('asset_report_id')->constrained()->restrictOnDelete();
            $t->jsonb('snapshot_payload');
            $t->foreignId('artifact_document_id')->constrained('documents')->restrictOnDelete();
            $t->char('artifact_checksum', 64);
            $t->char('snapshot_checksum', 64);
            $t->timestampTz('created_at')->useCurrent();
            $t->unique(['tenant_id', 'asset_report_id']);
            $t->foreign(['tenant_id', 'asset_report_id'])->references(['tenant_id', 'id'])->on('asset_reports')->restrictOnDelete();
            $t->foreign(['tenant_id', 'artifact_document_id'])->references(['tenant_id', 'id'])->on('documents')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
        Schema::dropIfExists('asset_reports');
        Schema::dropIfExists('report_template_versions');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('reporting_periods');
    }
};
