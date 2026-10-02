<?php

namespace App\Services\Lifecycle;

use App\Actions\Assets\ManageHistoricalAssetState;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetDisposalItem;
use App\Models\AssetMaintenance;
use App\Models\AssetSafeguard;
use App\Models\AssetTransfer;
use App\Models\AssetTransferItem;
use App\Models\AssetUsageDetermination;
use App\Models\AssetUsageItem;
use App\Models\AssetUtilization;
use App\Models\AssetValuation;
use App\Models\ExternalApproval;
use App\Models\SubjectType;
use App\Models\Tenant;
use App\Support\Tenancy\ActiveTenantMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ManageAssetLifecycleOperations
{
    public function __construct(
        private ActiveTenantMembership $memberships,
        private ManageHistoricalAssetState $history,
    ) {}

    /** @param array<string, mixed> $data */
    public function createMaintenance(Tenant $tenant, int|Model $actor, Asset $asset, array $data): AssetMaintenance
    {
        $actorId = $this->actorId($actor);
        $this->assertAsset($tenant, $actorId, $asset);

        return AssetMaintenance::query()->create([
            'tenant_id' => $tenant->id, 'asset_id' => $asset->id,
            'maintenance_type' => $data['maintenance_type'], 'planned_at' => $data['planned_at'] ?? null,
            'planned_cost' => $data['planned_cost'] ?? null, 'status' => 'planned', 'created_by' => $actorId,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function completeMaintenance(Tenant $tenant, int|Model $actor, AssetMaintenance $maintenance, array $data): AssetMaintenance
    {
        $actorId = $this->actorId($actor);
        $this->assertTenantMember($tenant, $actorId);
        $this->assertTenantRecord($tenant, $maintenance);
        if ($maintenance->status === 'completed') {
            return $maintenance;
        }
        $maintenance->update([
            'started_at' => $maintenance->started_at ?? now(), 'completed_at' => now(),
            'actual_cost' => $data['actual_cost'] ?? $maintenance->actual_cost,
            'condition_after' => $data['condition_after'] ?? null, 'notes' => $data['notes'] ?? $maintenance->notes,
            'status' => 'completed', 'updated_by' => $actorId,
        ]);

        return $maintenance->refresh();
    }

    /** @param array<string, mixed> $data */
    public function recordValuation(Tenant $tenant, int|Model $actor, Asset $asset, array $data): AssetValuation
    {
        $actorId = $this->actorId($actor);
        $this->assertAsset($tenant, $actorId, $asset);

        return AssetValuation::query()->create([
            'tenant_id' => $tenant->id, 'asset_id' => $asset->id, 'purpose' => $data['purpose'],
            'valuation_date' => $data['valuation_date'], 'amount' => $data['amount'],
            'valuer_name' => $data['valuer_name'], 'valuer_reference' => $data['valuer_reference'] ?? null,
            'authority_snapshot_id' => $data['authority_snapshot_id'] ?? null,
            'status' => $data['status'] ?? 'recorded', 'created_by' => $actorId,
        ]);
    }

    /** @param list<Asset> $assets */
    public function createTransfer(Tenant $tenant, int|Model $actor, string $type, array $assets): AssetTransfer
    {
        $actorId = $this->actorId($actor);
        $this->assertTenantMember($tenant, $actorId);
        if (! in_array($type, ['EXCHANGE', 'SALE'], true) || $assets === []) {
            throw new InvalidArgumentException('A valid transfer type and at least one asset are required.');
        }
        $locked = [];
        foreach ($assets as $asset) {
            $this->assertAsset($tenant, $actorId, $asset);
            $locked[$asset->id] = $asset;
        }

        return DB::transaction(function () use ($tenant, $actorId, $type, $locked) {
            $transfer = AssetTransfer::query()->create(['tenant_id' => $tenant->id, 'transfer_type' => $type, 'request_date' => today(), 'status' => 'requested', 'created_by' => $actorId]);
            foreach ($locked as $asset) {
                AssetTransferItem::query()->create(['tenant_id' => $tenant->id, 'asset_transfer_id' => $transfer->id, 'asset_id' => $asset->id, 'snapshot_payload' => $asset->only(['uuid', 'name', 'classification_id', 'lifecycle_status', 'current_location_id'])]);
            }

            return $transfer->load('items');
        });
    }

    /** @param array<string, mixed> $data */
    public function executeTransfer(Tenant $tenant, int|Model $actor, AssetTransfer $transfer, array $data): AssetTransfer
    {
        $actorId = $this->actorId($actor);
        $this->assertTenantMember($tenant, $actorId);
        $this->assertTenantRecord($tenant, $transfer);

        return DB::transaction(function () use ($tenant, $actorId, $transfer, $data) {
            $transfer = AssetTransfer::query()->whereKey($transfer->id)->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            if ($transfer->getAttribute('status') === 'executed') {
                return $transfer;
            }
            $locationId = $data['destination_location_id'] ?? null;
            if ($locationId === null) {
                throw new InvalidArgumentException('A destination location is required.');
            }
            foreach ($transfer->items()->lockForUpdate()->get() as $item) {
                $asset = Asset::query()->whereKey($item->getAttribute('asset_id'))->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
                $this->history->transitionLifecycle($asset, 'transferred', $actorId, now(), $asset->lock_version, 'transfer_execution');
                $this->history->move($asset->fresh(), $locationId, $actorId, now(), $asset->fresh()->lock_version, 'transfer_execution', null, null);
            }
            $transfer->update(['status' => 'executed', 'executed_at' => now(), 'lock_version' => ((int) $transfer->getAttribute('lock_version')) + 1, 'idempotency_key' => $data['idempotency_key'] ?? null]);

            return $transfer->refresh();
        });
    }

    /** @param list<Asset> $assets */
    public function createDisposal(Tenant $tenant, int|Model $actor, string $type, string $reason, array $assets): AssetDisposal
    {
        $actorId = $this->actorId($actor);
        $this->assertTenantMember($tenant, $actorId);
        if ($assets === [] || trim($reason) === '') {
            throw new InvalidArgumentException('Disposal reason and assets are required.');
        }
        foreach ($assets as $asset) {
            $this->assertAsset($tenant, $actorId, $asset);
        }

        return DB::transaction(function () use ($tenant, $actorId, $type, $reason, $assets) {
            $disposal = AssetDisposal::query()->create(['tenant_id' => $tenant->id, 'disposal_type' => $type, 'reason' => $reason, 'request_date' => today(), 'status' => 'requested', 'created_by' => $actorId]);
            foreach ($assets as $asset) {
                AssetDisposalItem::query()->create(['tenant_id' => $tenant->id, 'asset_disposal_id' => $disposal->id, 'asset_id' => $asset->id, 'snapshot_payload' => $asset->only(['uuid', 'name', 'classification_id', 'lifecycle_status'])]);
            }

            return $disposal->load('items');
        });
    }

    /** @param array<string, mixed> $data */
    public function recordUtilization(Tenant $tenant, int|Model $actor, Asset $asset, array $data): AssetUtilization
    {
        $actorId = $this->actorId($actor);
        $this->assertAsset($tenant, $actorId, $asset);

        return AssetUtilization::query()->create(['tenant_id' => $tenant->id, 'asset_id' => $asset->id, 'utilization_type' => $data['utilization_type'], 'counterparty' => $data['counterparty'] ?? null, 'start_at' => $data['start_at'], 'end_at' => $data['end_at'] ?? null, 'amount' => $data['amount'] ?? null, 'status' => $data['status'] ?? 'draft', 'lock_version' => 1]);
    }

    /** @param array<string, mixed> $data */
    public function recordSafeguard(Tenant $tenant, int|Model $actor, Asset $asset, array $data): AssetSafeguard
    {
        $actorId = $this->actorId($actor);
        $this->assertAsset($tenant, $actorId, $asset);

        return AssetSafeguard::query()->create(['tenant_id' => $tenant->id, 'asset_id' => $asset->id, 'safeguard_category' => $data['safeguard_category'], 'finding' => $data['finding'] ?? null, 'action' => $data['action'] ?? null, 'verification_status' => $data['verification_status'] ?? 'pending', 'created_by' => $actorId]);
    }

    /** @param array<string, mixed> $data */
    /** @param array<string, mixed> $data */
    public function executeDisposal(Tenant $tenant, int|Model $actor, AssetDisposal $disposal, array $data = []): AssetDisposal
    {
        $actorId = $this->actorId($actor);
        $this->assertTenantMember($tenant, $actorId);
        $this->assertTenantRecord($tenant, $disposal);

        return DB::transaction(function () use ($tenant, $actorId, $disposal, $data) {
            $disposal = AssetDisposal::query()->whereKey($disposal->id)->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            if ($disposal->getAttribute('status') === 'executed') {
                return $disposal;
            }
            foreach ($disposal->items()->lockForUpdate()->get() as $item) {
                $asset = Asset::query()->whereKey($item->getAttribute('asset_id'))->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
                $this->history->transitionLifecycle($asset, 'disposed', $actorId, now(), $asset->lock_version, 'disposal_execution', $disposal->getAttribute('reason'), $data['idempotency_key'] ?? null);
            }
            $disposal->update(['status' => 'executed', 'executed_at' => now(), 'lock_version' => ((int) $disposal->getAttribute('lock_version')) + 1, 'idempotency_key' => $data['idempotency_key'] ?? null]);

            return $disposal->refresh();
        });
    }

    public function createUsageDetermination(Tenant $tenant, int|Model $actor, int $year, ?string $decisionNumber = null): AssetUsageDetermination
    {
        $actorId = $this->actorId($actor);
        $this->assertTenantMember($tenant, $actorId);

        return AssetUsageDetermination::query()->create(['tenant_id' => $tenant->id, 'period_year' => $year, 'decision_number' => $decisionNumber, 'status' => 'draft', 'created_by' => $actorId]);
    }

    /** @param array<string, mixed> $snapshot */
    /** @param array<string, mixed> $snapshot */
    public function addUsageItem(Tenant $tenant, int|Model $actor, AssetUsageDetermination $determination, Asset $asset, array $snapshot = []): AssetUsageItem
    {
        $actorId = $this->actorId($actor);
        $this->assertTenantMember($tenant, $actorId);
        $determination = AssetUsageDetermination::query()->whereKey($determination->id)->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertAsset($tenant, $actorId, $asset);
        if ($determination->status !== 'draft') {
            throw new \RuntimeException('Finalized usage determination is immutable.');
        }

        return AssetUsageItem::query()->create(['tenant_id' => $tenant->id, 'usage_determination_id' => $determination->id, 'asset_id' => $asset->id, 'snapshot_payload' => $snapshot + $asset->only(['uuid', 'name', 'classification_id', 'lifecycle_status'])]);
    }

    public function finalizeUsageDetermination(Tenant $tenant, int|Model $actor, AssetUsageDetermination $determination): AssetUsageDetermination
    {
        $this->assertTenantMember($tenant, $this->actorId($actor));

        return DB::transaction(function () use ($tenant, $determination) {
            $determination = AssetUsageDetermination::query()->whereKey($determination->id)->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            if ($determination->status !== 'draft' || ! AssetUsageItem::query()->where('tenant_id', $tenant->id)->where('usage_determination_id', $determination->id)->exists()) {
                throw new \RuntimeException('Usage determination is not ready for finalization.');
            }
            $determination->update(['status' => 'finalized', 'finalized_at' => now(), 'lock_version' => $determination->lock_version + 1]);

            return $determination->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    /** @param array<string, mixed> $data */
    public function recordExternalApproval(Tenant $tenant, int|Model $actor, SubjectType $subjectType, int $subjectId, array $data): ExternalApproval
    {
        $actorId = $this->actorId($actor);
        $this->assertTenantMember($tenant, $actorId);
        if (isset($data['document_id']) && ! DB::table('documents')->where('id', $data['document_id'])->where('tenant_id', $tenant->id)->exists()) {
            throw new AuthorizationException('Approval evidence is outside the active tenant.');
        }

        return ExternalApproval::query()->create($data + ['tenant_id' => $tenant->id, 'subject_type_id' => $subjectType->id, 'subject_id' => $subjectId, 'recorded_by' => $actorId, 'status' => 'recorded']);
    }

    private function actorId(int|Model $actor): int
    {
        return $actor instanceof Model ? (int) $actor->getKey() : $actor;
    }

    private function assertTenantMember(Tenant $tenant, int $actorId): void
    {
        if ($tenant->status !== 'active' || ! $this->memberships->exists($actorId, $tenant->id)) {
            throw new AuthorizationException('Active tenant membership is required.');
        }
    }

    private function assertAsset(Tenant $tenant, int $actorId, Asset $asset): void
    {
        $this->assertTenantMember($tenant, $actorId);
        if ((int) $asset->tenant_id !== (int) $tenant->id) {
            throw new AuthorizationException('Asset is outside the active tenant.');
        }
    }

    private function assertTenantRecord(Tenant $tenant, Model $record): void
    {
        if ((int) $record->getAttribute('tenant_id') !== (int) $tenant->id) {
            throw new AuthorizationException('Resource is outside the active tenant.');
        }
    }
}
