<?php

namespace App\Services\Interoperability;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\IntegrationExport;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Authorization\PermissionResolver;
use App\Support\Tenancy\ActiveTenantMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ImportExportService
{
    public function __construct(private ActiveTenantMembership $memberships, private PermissionResolver $permissions) {}

    /** @param list<array<string,mixed>> $rows */
    public function preview(Tenant $tenant, User $actor, array $rows, string $importType = 'assets', string $strategy = 'atomic'): ImportJob
    {
        $this->authorizeAny($tenant, $actor, ['imports.create', 'assets.create']);
        if ($importType !== 'assets' || ! in_array($strategy, ['atomic', 'partial'], true)) {
            throw new RuntimeException('Unsupported import contract.');
        }

        return DB::transaction(function () use ($tenant, $actor, $rows, $importType, $strategy): ImportJob {
            $job = ImportJob::query()->create(['tenant_id' => $tenant->id, 'import_type' => $importType, 'status' => 'previewed', 'strategy' => $strategy, 'total_rows' => count($rows), 'requested_by' => $actor->id]);
            $valid = 0;
            foreach ($rows as $i => $payload) {
                $row = ImportRow::query()->create(['tenant_id' => $tenant->id, 'import_job_id' => $job->id, 'row_number' => $i + 1, 'raw_payload' => $payload, 'status' => 'valid']);
                foreach ($this->errors($tenant, $payload, $rows) as $error) {
                    $row->update(['status' => 'invalid']);
                    ImportError::query()->create(['tenant_id' => $tenant->id, 'import_job_id' => $job->id, 'import_row_id' => $row->id, ...$error]);
                }
                if ($row->status === 'valid') {
                    $valid++;
                    $row->update(['normalized_payload' => $this->normalize($payload)]);
                }
            }

            return $job->update(['valid_rows' => $valid, 'invalid_rows' => count($rows) - $valid]) ? $job->refresh() : $job;
        });
    }

    public function confirm(Tenant $tenant, User $actor, ImportJob $job): ImportJob
    {
        $this->authorizeAny($tenant, $actor, ['imports.commit', 'assets.create']);

        return DB::transaction(function () use ($tenant, $actor, $job): ImportJob {
            $job = ImportJob::query()->where('tenant_id', $tenant->id)->whereKey($job->id)->lockForUpdate()->firstOrFail();
            if ($job->status === 'completed') {
                return $job;
            }
            if ($job->status !== 'previewed') {
                throw new RuntimeException('Import must be previewed before confirmation.');
            }
            if ($job->strategy === 'atomic' && $job->invalid_rows > 0) {
                throw new RuntimeException('Atomic import contains invalid rows.');
            }
            foreach (ImportRow::query()->where('tenant_id', $tenant->id)->where('import_job_id', $job->id)->where('status', 'valid')->lockForUpdate()->get() as $row) {
                $asset = Asset::query()->create([...$row->normalized_payload, 'tenant_id' => $tenant->id, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
                $row->update(['status' => 'imported', 'created_resource_type' => 'asset', 'created_resource_id' => $asset->id]);
            }
            $job->update(['status' => 'completed', 'imported_rows' => ImportRow::query()->where('import_job_id', $job->id)->where('status', 'imported')->count()]);
            AuditLog::query()->create(['tenant_id' => $tenant->id, 'actor_id' => $actor->id, 'action' => 'import.confirmed', 'subject_type' => 'import_job', 'subject_id' => $job->id, 'before_state' => ['status' => 'previewed'], 'after_state' => $job->fresh()->toArray(), 'occurred_at' => now()]);

            return $job->fresh();
        });
    }

    public function prepareExport(Tenant $tenant, User $actor, string $targetSystem = 'internal-boundary'): IntegrationExport
    {
        $this->authorize($tenant, $actor, 'reports.export');

        return IntegrationExport::query()->create(['tenant_id' => $tenant->id, 'target_system' => $targetSystem, 'format_version' => '1', 'status' => 'READY_FOR_EXPORT', 'requested_by' => $actor->id, 'metadata' => ['contract' => 'B14', 'integration' => 'not_claimed']]);
    }

    public function markExported(Tenant $tenant, User $actor, IntegrationExport $export): IntegrationExport
    {
        $this->authorize($tenant, $actor, 'reports.export');

        return DB::transaction(function () use ($tenant, $actor, $export): IntegrationExport {
            $export = IntegrationExport::query()->where('tenant_id', $tenant->id)->whereKey($export->id)->lockForUpdate()->firstOrFail();
            if ($export->status === 'EXPORTED') {
                return $export;
            }
            if ($export->status !== 'READY_FOR_EXPORT') {
                throw new RuntimeException('Export is not ready.');
            }
            $payload = Asset::query()->where('tenant_id', $tenant->id)->orderBy('id')->get()->map(fn (Asset $a) => ['uuid' => $a->uuid, 'asset_code' => $a->asset_code, 'name' => $a->name, 'quantity' => $a->quantity])->all();
            $bytes = json_encode(['tenant_uuid' => $tenant->uuid, 'assets' => $payload], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $path = 'exports/'.$tenant->uuid.'/'.$export->uuid.'.json';
            Storage::disk('private')->put($path, $bytes);
            $checksum = hash('sha256', $bytes);
            $doc = Document::query()->create(['tenant_id' => $tenant->id, 'document_type' => 'integration_export', 'storage_disk' => 'private', 'storage_path' => $path, 'original_filename' => $export->uuid.'.json', 'mime_type' => 'application/json', 'size_bytes' => strlen($bytes), 'checksum' => $checksum, 'uploaded_by' => $actor->id, 'malware_scan_status' => 'not_available', 'storage_state' => 'stored', 'visibility' => 'private', 'classification' => 'sensitive']);
            $export->update(['status' => 'EXPORTED', 'generated_document_id' => $doc->id, 'checksum' => $checksum, 'generated_at' => now(), 'exported_at' => now()]);
            AuditLog::query()->create(['tenant_id' => $tenant->id, 'actor_id' => $actor->id, 'action' => 'integration.exported', 'subject_type' => 'integration_export', 'subject_id' => $export->id, 'before_state' => ['status' => 'READY_FOR_EXPORT'], 'after_state' => ['status' => 'EXPORTED', 'checksum' => $checksum], 'occurred_at' => now()]);

            return $export->fresh();
        });
    }

    public function reconcile(Tenant $tenant, User $actor, IntegrationExport $export): IntegrationExport
    {
        $this->authorize($tenant, $actor, 'reports.export');
        if ($export->tenant_id !== $tenant->id || $export->status !== 'EXPORTED') {
            throw new RuntimeException('Only an exported tenant-scoped record can be reconciled.');
        }
        $export->update(['status' => 'RECONCILED', 'reconciled_at' => now()]);
        AuditLog::query()->create(['tenant_id' => $tenant->id, 'actor_id' => $actor->id, 'action' => 'integration.reconciled', 'subject_type' => 'integration_export', 'subject_id' => $export->id, 'before_state' => ['status' => 'EXPORTED'], 'after_state' => ['status' => 'RECONCILED'], 'occurred_at' => now()]);

        return $export->refresh();
    }

    /**
     * @param  array<string, mixed>  $p
     * @return list<array{field_name: string, error_code: string, message: string}>
     */
    /**
     * @param  array<string, mixed>  $p
     * @param  list<array<string, mixed>>  $allRows
     * @return list<array{field_name: string, error_code: string, message: string}>
     */
    private function errors(Tenant $tenant, array $p, array $allRows): array
    {
        $e = [];
        $assetCode = isset($p['asset_code']) ? trim((string) $p['asset_code']) : '';
        if ($assetCode !== '' && Asset::query()->where('tenant_id', $tenant->id)->where('asset_code', $assetCode)->exists()) {
            $e[] = ['field_name' => 'asset_code', 'error_code' => 'duplicate_asset_code', 'message' => 'Kode aset sudah digunakan pada desa aktif.'];
        }
        if ($assetCode !== '' && collect($allRows)->filter(fn (array $row) => trim((string) ($row['asset_code'] ?? '')) === $assetCode)->count() > 1) {
            $e[] = ['field_name' => 'asset_code', 'error_code' => 'duplicate_in_file', 'message' => 'Kode aset duplikat di dalam file impor.'];
        }
        if (array_key_exists('tenant_id', $p)) {
            $e[] = ['field_name' => 'tenant_id', 'error_code' => 'forbidden_field', 'message' => 'Tenant ownership comes from active context.'];
        } if (! isset($p['name']) || ! is_string($p['name']) || trim($p['name']) === '') {
            $e[] = ['field_name' => 'name', 'error_code' => 'required', 'message' => 'Name is required.'];
        } if (! isset($p['classification_id']) || ! is_int($p['classification_id'])) {
            $e[] = ['field_name' => 'classification_id', 'error_code' => 'required', 'message' => 'Classification is required.'];
        } elseif (! AssetClassification::query()->whereKey($p['classification_id'])->exists()) {
            $e[] = ['field_name' => 'classification_id', 'error_code' => 'not_found', 'message' => 'Classification is unavailable.'];
        }

        return $e;
    }

    /**
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    private function normalize(array $p): array
    {
        return array_intersect_key($p, array_flip(['uuid', 'classification_id', 'asset_code', 'register_number', 'name', 'description', 'acquisition_date', 'acquisition_year', 'acquisition_origin', 'funding_source_id', 'quantity', 'unit_id', 'unit_price', 'acquisition_value', 'condition', 'lifecycle_status', 'verification_status', 'current_location_id']));
    }

    /** @param list<string> $permissions */
    private function authorizeAny(Tenant $tenant, User $actor, array $permissions): void
    {
        if (! $tenant->isOperational() || ! $this->memberships->exists($actor->id, $tenant->id)) {
            throw new AuthorizationException('Tenant permission is required.');
        }
        foreach ($permissions as $permission) {
            if ($this->permissions->allows($actor, $tenant, $permission)) {
                return;
            }
        }

        throw new AuthorizationException('Tenant permission is required.');
    }

    private function authorize(Tenant $tenant, User $actor, string $permission): void
    {
        if (! $tenant->isOperational() || ! $this->memberships->exists($actor->id, $tenant->id) || ! $this->permissions->allows($actor, $tenant, $permission)) {
            throw new AuthorizationException('Tenant permission is required.');
        }
    }
}
