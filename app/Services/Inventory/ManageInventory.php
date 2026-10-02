<?php

namespace App\Services\Inventory;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\InventoryDiscovery;
use App\Models\InventoryDiscrepancy;
use App\Models\InventoryItem;
use App\Models\InventoryReconciliation;
use App\Models\InventorySession;
use App\Models\Tenant;
use App\Support\Tenancy\ActiveTenantMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class ManageInventory
{
    private const RESULTS = ['matched', 'missing', 'relocated', 'condition_mismatch', 'duplicate_suspected'];

    private const RESOLUTIONS = ['no_change', 'asset_correction', 'mutation', 'condition_update', 'new_asset', 'duplicate_investigation', 'other'];

    public function __construct(private ActiveTenantMembership $memberships) {}

    /** @param array<string, mixed> $scope */
    public function createSession(Tenant $tenant, int|Model $actor, string $name, string $periodLabel, array $scope, \DateTimeInterface $referenceAt): InventorySession
    {
        $actorId = $this->actorId($actor);
        $this->assertMember($tenant, $actorId);
        if (trim($name) === '' || trim($periodLabel) === '') {
            throw new InvalidArgumentException('Inventory name and period are required.');
        }

        $session = InventorySession::query()->create([
            'tenant_id' => $tenant->id, 'name' => $name, 'period_label' => $periodLabel,
            'scope_definition' => $scope, 'reference_at' => $referenceAt, 'status' => 'draft',
            'created_by' => $actorId, 'lock_version' => 1,
        ]);
        $this->auditTenant($tenant->id, $actorId, 'inventory.session.created', $session, null, $session->toArray());

        return $session;
    }

    public function freezeDataset(InventorySession $session): InventorySession
    {
        $this->assertTenantSession($session);

        return DB::transaction(function () use ($session) {
            $session = InventorySession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($session->status !== 'draft') {
                return $session;
            }
            $scope = $session->scope_definition;
            $assets = Asset::query()->where('tenant_id', $session->tenant_id)
                ->when($scope['asset_ids'] ?? null, fn ($q, $ids) => $q->whereIn('id', $ids))
                ->when($scope['location_ids'] ?? null, fn ($q, $ids) => $q->whereIn('current_location_id', $ids))
                ->when($scope['classification_ids'] ?? null, fn ($q, $ids) => $q->whereIn('classification_id', $ids))
                ->with(['classification'])
                ->orderBy('id')->get();

            foreach ($assets as $asset) {
                $location = $asset->current_location_id
                    ? DB::table('asset_locations')->where('tenant_id', $session->tenant_id)->where('id', $asset->current_location_id)->first()
                    : null;
                InventoryItem::query()->create([
                    'tenant_id' => $session->tenant_id, 'inventory_session_id' => $session->id, 'asset_id' => $asset->id,
                    'asset_uuid_snapshot' => $asset->uuid, 'asset_code_snapshot' => $asset->asset_code,
                    'register_number_snapshot' => $asset->register_number, 'asset_name_snapshot' => $asset->name,
                    'classification_id_snapshot' => $asset->classification_id, 'classification_code_snapshot' => $asset->classification?->code,
                    'classification_name_snapshot' => $asset->classification?->name, 'expected_location_id' => $asset->current_location_id,
                    'expected_location_snapshot' => $location ? (array) $location : null, 'expected_condition' => $asset->condition,
                    'lifecycle_status_snapshot' => $asset->lifecycle_status, 'verification_result' => 'not_checked',
                    'snapshot_payload' => $asset->only(['uuid', 'asset_code', 'register_number', 'name', 'condition', 'lifecycle_status']),
                ]);
            }
            $session->update(['status' => 'prepared', 'started_at' => now(), 'lock_version' => $session->lock_version + 1]);
            $this->auditTenant($session->tenant_id, (int) $session->created_by, 'inventory.dataset.frozen', $session, null, ['item_count' => $assets->count()]);

            return $session->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function observeItem(InventorySession $session, InventoryItem $item, int|Model $actor, array $data): InventoryItem
    {
        $actorId = $this->actorId($actor);
        $this->assertMember($this->tenant($session->tenant_id), $actorId);
        $this->assertItem($session, $item);
        $result = $data['result'] ?? null;
        if (! in_array($result, self::RESULTS, true)) {
            throw new InvalidArgumentException('Unsupported inventory verification result.');
        }

        return DB::transaction(function () use ($session, $item, $actorId, $data, $result) {
            $session = InventorySession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if (in_array($session->status, ['finalized', 'cancelled'], true)) {
                throw new RuntimeException('Finalized inventory is immutable.');
            }
            $item = InventoryItem::query()->whereKey($item->id)->where('tenant_id', $session->tenant_id)->lockForUpdate()->firstOrFail();
            $item->update([
                'observed_location_id' => $data['observed_location_id'] ?? null,
                'observed_location_snapshot' => $data['observed_location_snapshot'] ?? null,
                'observed_condition' => $data['observed_condition'] ?? null,
                'verification_result' => $result, 'verified_by' => $actorId, 'verified_at' => now(),
            ]);
            if ($result !== 'matched') {
                InventoryDiscrepancy::query()->firstOrCreate([
                    'tenant_id' => $session->tenant_id, 'inventory_session_id' => $session->id,
                    'inventory_item_id' => $item->id, 'discrepancy_type' => $result, 'status' => 'open',
                ], ['asset_id' => $item->asset_id, 'description' => 'Inventory observation requires reconciliation.']);
            }
            $this->auditTenant($session->tenant_id, $actorId, 'inventory.item.observed', $item, null, $item->fresh()->toArray());

            return $item->refresh();
        });
    }

    public function createDiscovery(InventorySession $session, int|Model $actor, string $label, string $description): InventoryDiscovery
    {
        $actorId = $this->actorId($actor);
        $this->assertMember($this->tenant($session->tenant_id), $actorId);
        if (trim($label) === '' || trim($description) === '') {
            throw new InvalidArgumentException('Discovery label and description are required.');
        }
        $this->assertSessionWritable($session);

        return InventoryDiscovery::query()->create(['tenant_id' => $session->tenant_id, 'inventory_session_id' => $session->id, 'temporary_label' => $label, 'description' => $description, 'resolution_status' => 'discovered', 'created_by' => $actorId]);
    }

    public function reconcile(InventoryDiscrepancy $discrepancy, int|Model $actor, string $resolutionType, string $notes, string $idempotencyKey): InventoryReconciliation
    {
        $actorId = $this->actorId($actor);
        $tenant = $this->tenant((int) $discrepancy->tenant_id);
        $this->assertMember($tenant, $actorId);
        if (! in_array($resolutionType, self::RESOLUTIONS, true) || trim($notes) === '' || trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Controlled reconciliation data is required.');
        }

        return DB::transaction(function () use ($discrepancy, $actorId, $resolutionType, $notes, $idempotencyKey) {
            if ($existing = InventoryReconciliation::query()->where('tenant_id', $discrepancy->tenant_id)->where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }
            $discrepancy = InventoryDiscrepancy::query()->whereKey($discrepancy->id)->where('tenant_id', $discrepancy->tenant_id)->lockForUpdate()->firstOrFail();
            if ($discrepancy->status !== 'open') {
                throw new RuntimeException('Discrepancy is already resolved.');
            }
            $reconciliation = InventoryReconciliation::query()->create([
                'tenant_id' => $discrepancy->tenant_id, 'inventory_discrepancy_id' => $discrepancy->id,
                'resolution_type' => $resolutionType, 'decision_notes' => $notes, 'reconciled_by' => $actorId,
                'reconciled_at' => now(), 'idempotency_key' => $idempotencyKey,
            ]);
            $discrepancy->update(['status' => 'reconciled', 'reviewed_by' => $actorId, 'reviewed_at' => now(), 'proposed_resolution' => $notes]);
            $this->auditTenant($discrepancy->tenant_id, $actorId, 'inventory.discrepancy.reconciled', $reconciliation, null, $reconciliation->toArray());

            return $reconciliation;
        });
    }

    public function resolveDiscovery(InventoryDiscovery $discovery, int|Model $actor, string $resolutionType): InventoryDiscovery
    {
        $actorId = $this->actorId($actor);
        $tenant = $this->tenant((int) $discovery->tenant_id);
        $this->assertMember($tenant, $actorId);
        if (! in_array($resolutionType, ['link_existing', 'register_new', 'unidentified', 'duplicate_suspected'], true)) {
            throw new InvalidArgumentException('Controlled discovery resolution is required.');
        }
        $this->assertSessionWritable(InventorySession::query()->findOrFail($discovery->inventory_session_id));

        return DB::transaction(function () use ($discovery, $actorId, $resolutionType) {
            $discovery = InventoryDiscovery::query()->whereKey($discovery->id)->where('tenant_id', $discovery->tenant_id)->lockForUpdate()->firstOrFail();
            if ($discovery->resolution_status !== 'discovered') {
                throw new RuntimeException('Discovery is already resolved.');
            }
            $discovery->update(['resolution_status' => $resolutionType, 'resolved_by' => $actorId, 'resolved_at' => now()]);

            return $discovery->refresh();
        });
    }

    public function finalize(InventorySession $session, int|Model $actor): InventorySession
    {
        $actorId = $this->actorId($actor);
        $this->assertMember($this->tenant($session->tenant_id), $actorId);

        return DB::transaction(function () use ($session, $actorId) {
            $session = InventorySession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($session->status === 'finalized') {
                return $session;
            }
            if (! in_array($session->status, ['prepared', 'in_progress', 'review', 'reconciliation'], true) || $session->items()->where('verification_result', 'not_checked')->exists() || $session->discrepancies()->where('status', 'open')->exists()) {
                throw new RuntimeException('Inventory is not ready for finalization.');
            }
            $session->update(['status' => 'finalized', 'reviewed_at' => now(), 'finalized_at' => now(), 'lock_version' => $session->lock_version + 1]);
            $this->auditTenant($session->tenant_id, $actorId, 'inventory.session.finalized', $session, null, $session->fresh()->toArray());

            return $session->refresh();
        });
    }

    private function assertSessionWritable(InventorySession $session): void
    {
        if (! in_array($session->status, ['draft', 'prepared', 'in_progress', 'review', 'reconciliation'], true)) {
            throw new RuntimeException('Inventory session is immutable.');
        }
    }

    private function assertTenantSession(InventorySession $session): void
    {
        $this->assertMember($this->tenant((int) $session->tenant_id), (int) $session->created_by);
    }

    private function assertItem(InventorySession $session, InventoryItem $item): void
    {
        if ((int) $item->tenant_id !== (int) $session->tenant_id || (int) $item->inventory_session_id !== (int) $session->id) {
            throw new AuthorizationException('Inventory item is outside the active tenant/session.');
        }
    }

    private function assertMember(Tenant $tenant, int $actorId): void
    {
        if ($tenant->status !== 'active' || ! $this->memberships->exists($actorId, $tenant->id)) {
            throw new AuthorizationException('Active tenant membership is required.');
        }
    }

    private function tenant(int $id): Tenant
    {
        return Tenant::query()->findOrFail($id);
    }

    private function actorId(int|Model $actor): int
    {
        return $actor instanceof Model ? (int) $actor->getKey() : $actor;
    }

    /** @param array<string, mixed>|null $before
     * @param  array<string, mixed>|null  $after
     */
    private function auditTenant(int $tenantId, int $actorId, string $action, Model $subject, ?array $before, ?array $after): void
    {
        AuditLog::query()->create(['tenant_id' => $tenantId, 'actor_id' => $actorId, 'action' => $action, 'subject_type' => $subject::class, 'subject_id' => $subject->getKey(), 'before_state' => $before, 'after_state' => $after, 'occurred_at' => now()]);
    }
}
