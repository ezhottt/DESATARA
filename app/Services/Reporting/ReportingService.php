<?php

namespace App\Services\Reporting;

use App\Models\Asset;
use App\Models\AssetReport;
use App\Models\AuditLog;
use App\Models\AuthoritySnapshot;
use App\Models\Document;
use App\Models\ReportingPeriod;
use App\Models\ReportSnapshot;
use App\Models\ReportTemplateVersion;
use App\Models\Tenant;
use App\Models\TenantOfficial;
use App\Models\User;
use App\Support\Authority\AuthoritySnapshotter;
use App\Support\Authorization\PermissionResolver;
use App\Support\Tenancy\ActiveTenantMembership;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

final class ReportingService
{
    public function __construct(private ActiveTenantMembership $memberships, private PermissionResolver $permissions, private AuthoritySnapshotter $authorities) {}

    public function createPeriod(Tenant $tenant, User $actor, int $year, string $type, ?int $number, string $start, string $end, ?string $deadline = null): ReportingPeriod
    {
        $this->member($tenant, $actor);
        if ($type === 'semester' && ! in_array($number, [1, 2], true)) {
            throw new InvalidArgumentException('Semester period number must be 1 or 2.');
        }
        if ($type !== 'semester' && $number !== null) {
            throw new InvalidArgumentException('Only semester periods have a period number.');
        }
        if ($end < $start) {
            throw new InvalidArgumentException('Reporting period end must not precede its start.');
        }

        return ReportingPeriod::query()->create(['tenant_id' => $tenant->id, 'year' => $year, 'period_type' => $type, 'period_number' => $number, 'start_date' => $start, 'end_date' => $end, 'deadline' => $deadline, 'status' => 'preparation']);
    }

    public function generate(Tenant $tenant, User $actor, ReportingPeriod $period, ReportTemplateVersion $template, int $revisionNumber = 1): AssetReport
    {
        $this->authorize($tenant, $actor, 'reports.view');
        if ($period->tenant_id !== $tenant->id || $template->status !== 'published') {
            throw new AuthorizationException('Report definition is outside the active tenant or unavailable.');
        }
        if ($template->effective_from && CarbonImmutable::parse((string) $period->end_date)->lt(CarbonImmutable::parse((string) $template->effective_from))) {
            throw new RuntimeException('Report template is not effective for this period.');
        }
        if ($template->effective_until && CarbonImmutable::parse((string) $period->start_date)->gt(CarbonImmutable::parse((string) $template->effective_until))) {
            throw new RuntimeException('Report template is not effective for this period.');
        }

        return AssetReport::query()->create(['tenant_id' => $tenant->id, 'reporting_period_id' => $period->id, 'report_template_version_id' => $template->id, 'status' => $revisionNumber > 1 ? 'revision' : 'draft', 'revision_number' => $revisionNumber, 'generated_by' => $actor->id, 'generated_at' => now()]);
    }

    public function review(Tenant $tenant, User $actor, AssetReport $report): AssetReport
    {
        $this->authorizeReport($tenant, $actor, $report, 'reports.view');
        if (! in_array($report->status, ['draft', 'revision'], true)) {
            throw new RuntimeException('Only draft reports can be reviewed.');
        }

        $report->update(['status' => 'review', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'lock_version' => $report->lock_version + 1]);

        return $report->refresh();
    }

    public function finalize(Tenant $tenant, User $actor, AssetReport $report): AssetReport
    {
        $this->authorizeReport($tenant, $actor, $report, 'reports.export');

        return DB::transaction(function () use ($tenant, $actor, $report): AssetReport {
            $report = AssetReport::query()->whereKey($report->id)->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            if ($report->status === 'finalized') {
                return $report->load('snapshot');
            }
            if ($report->status !== 'review') {
                throw new RuntimeException('Report review is required before finalization.');
            }
            $template = ReportTemplateVersion::query()->findOrFail($report->report_template_version_id);
            $period = ReportingPeriod::query()->where('tenant_id', $tenant->id)->findOrFail($report->reporting_period_id);
            $authority = $this->authorityContext($tenant, $actor, $template);
            $regulatoryContext = json_decode((string) $template->getRawOriginal('regulatory_context'), true);
            $schemaDefinition = json_decode((string) $template->getRawOriginal('schema_definition'), true);
            $regulatoryContext = is_array($regulatoryContext) ? $regulatoryContext : [];
            $schemaDefinition = is_array($schemaDefinition) ? $schemaDefinition : [];
            $payload = [
                'tenant' => ['id' => $tenant->id, 'uuid' => $tenant->uuid, 'name' => $tenant->name],
                'reporting_period' => $period->toArray(),
                'template' => ['id' => $template->id, 'version' => $template->version, 'schema_definition' => $schemaDefinition],
                'regulatory_context' => $regulatoryContext,
                'official_context' => TenantOfficial::query()->where('tenant_id', $tenant->id)->orderBy('position_name')->get()->map(fn (TenantOfficial $official): array => ['official_type' => $official->official_type, 'position_name' => $official->position_name, 'authority_code' => $official->authority_code, 'authority_scope' => $official->authority_scope, 'appointment_number' => $official->appointment_number, 'valid_from' => $official->valid_from ? CarbonImmutable::parse((string) $official->valid_from)->toDateString() : null, 'valid_until' => $official->valid_until ? CarbonImmutable::parse((string) $official->valid_until)->toDateString() : null, 'status' => $official->status])->all(),
                'authority_context' => $authority?->toArray(),
                'dataset' => $this->dataset($tenant),
                'generated_by' => $report->generated_by,
                'finalized_by' => $actor->id,
                'finalized_at' => now()->toIso8601String(),
            ];
            $bytes = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $path = 'reports/'.$tenant->uuid.'/'.$report->uuid.'.json';
            Storage::disk('private')->put($path, $bytes);
            $checksum = hash('sha256', $bytes);
            $document = Document::query()->create(['tenant_id' => $tenant->id, 'document_type' => 'report_artifact', 'storage_disk' => 'private', 'storage_path' => $path, 'original_filename' => $report->uuid.'.json', 'mime_type' => 'application/json', 'size_bytes' => strlen($bytes), 'checksum' => $checksum, 'uploaded_by' => $actor->id, 'malware_scan_status' => 'not_available', 'storage_state' => 'stored', 'visibility' => 'private', 'classification' => 'sensitive']);
            ReportSnapshot::query()->create(['tenant_id' => $tenant->id, 'asset_report_id' => $report->id, 'snapshot_payload' => $payload, 'artifact_document_id' => $document->id, 'artifact_checksum' => $checksum, 'snapshot_checksum' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))]);
            $report->update(['status' => 'finalized', 'finalized_by' => $actor->id, 'finalized_at' => now(), 'lock_version' => $report->lock_version + 1]);
            AuditLog::query()->create(['tenant_id' => $tenant->id, 'actor_id' => $actor->id, 'action' => 'report.finalized', 'subject_type' => 'asset_report', 'subject_id' => $report->id, 'before_state' => ['status' => 'review'], 'after_state' => ['status' => 'finalized', 'snapshot_checksum' => $checksum], 'occurred_at' => now()]);

            return $report->fresh('snapshot');
        });
    }

    public function revise(Tenant $tenant, User $actor, AssetReport $report): AssetReport
    {
        $this->authorizeReport($tenant, $actor, $report, 'reports.view');
        if ($report->status !== 'finalized') {
            throw new RuntimeException('Only finalized reports can be revised.');
        }
        $revision = $this->generate($tenant, $actor, ReportingPeriod::query()->where('tenant_id', $tenant->id)->findOrFail($report->reporting_period_id), ReportTemplateVersion::query()->findOrFail($report->report_template_version_id), $report->revision_number + 1);
        $revision->update(['parent_report_id' => $report->id]);

        return $revision->refresh();
    }

    public function export(Tenant $tenant, User $actor, AssetReport $report): Document
    {
        $this->authorizeReport($tenant, $actor, $report, 'reports.export');
        if ($report->status !== 'finalized') {
            throw new RuntimeException('Only finalized reports can be exported.');
        }

        return Document::query()->where('tenant_id', $tenant->id)->findOrFail($report->snapshot()->value('artifact_document_id'));
    }

    /** @return list<array<string, mixed>> */
    private function dataset(Tenant $tenant): array
    {
        return Asset::query()->where('tenant_id', $tenant->id)->with('classification')->orderBy('id')->get()->map(fn (Asset $asset): array => ['id' => $asset->id, 'uuid' => $asset->uuid, 'asset_code' => $asset->asset_code, 'register_number' => $asset->register_number, 'name' => $asset->name, 'classification' => $asset->classification ? ['id' => $asset->classification->id, 'code' => $asset->classification->code, 'name' => $asset->classification->name, 'version_id' => $asset->classification->classification_version_id] : null, 'quantity' => $asset->quantity, 'unit_price' => $asset->unit_price, 'acquisition_value' => $asset->acquisition_value, 'condition' => $asset->condition, 'lifecycle_status' => $asset->lifecycle_status, 'verification_status' => $asset->verification_status])->all();
    }

    private function authorityContext(Tenant $tenant, User $actor, ReportTemplateVersion $template): ?AuthoritySnapshot
    {
        $context = json_decode((string) $template->getRawOriginal('regulatory_context'), true);
        if (! is_array($context)) {
            return null;
        }
        if (! isset($context['authority_code']) || ! is_string($context['authority_code'])) {
            return null;
        }

        return $this->authorities->capture($actor, $tenant, $context['authority_code'], $context['authority_scope'] ?? 'reports');
    }

    private function authorizeReport(Tenant $tenant, User $actor, AssetReport $report, string $permission): void
    {
        if ($report->tenant_id !== $tenant->id) {
            throw new AuthorizationException('Report is outside the active tenant.');
        }
        $this->authorize($tenant, $actor, $permission);
    }

    private function authorize(Tenant $tenant, User $actor, string $permission): void
    {
        $this->member($tenant, $actor);
        if (! $this->permissions->allows($actor, $tenant, $permission)) {
            throw new AuthorizationException('Report permission is required.');
        }
    }

    private function member(Tenant $tenant, User $actor): void
    {
        if (! $tenant->isOperational() || ! $this->memberships->exists($actor->id, $tenant->id)) {
            throw new AuthorizationException('Active tenant membership is required.');
        }
    }
}
