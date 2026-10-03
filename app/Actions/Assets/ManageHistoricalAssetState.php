<?php

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetClassificationAssignment;
use App\Models\AssetConditionEvent;
use App\Models\AssetCorrection;
use App\Models\AssetLifecycleEvent;
use App\Models\AssetMutation;
use App\Models\AssetResponsibilityAssignment;
use App\Models\Tenant;
use App\Support\Tenancy\ActiveTenantMembership;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ManageHistoricalAssetState
{
    public function __construct(private ActiveTenantMembership $memberships) {}

    public function reclassify(Asset $asset, int $classificationId, int $actorId, DateTimeInterface $effectiveAt, int $lockVersion, ?string $reason = null): AssetClassificationAssignment
    {
        return DB::transaction(function () use ($asset, $classificationId, $actorId, $effectiveAt, $lockVersion, $reason) {
            $this->assertActor($asset, $actorId);
            $asset = $this->current($asset, $lockVersion);
            AssetClassificationAssignment::query()->where('tenant_id', $asset->tenant_id)->where('asset_id', $asset->id)->whereNull('valid_until')->update(['valid_until' => $effectiveAt]);
            $assignment = AssetClassificationAssignment::query()->create([
                'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id, 'classification_id' => $classificationId,
                'valid_from' => $effectiveAt, 'assignment_type' => 'reclassification', 'reason' => $reason,
                'assigned_by' => $actorId,
            ]);
            $this->updateProjection($asset, $lockVersion, ['classification_id' => $classificationId, 'updated_by' => $actorId]);

            return $assignment;
        });
    }

    public function move(Asset $asset, int $destinationId, int $actorId, DateTimeInterface $effectiveAt, int $lockVersion, string $type, ?string $reason = null, ?string $idempotencyKey = null): AssetMutation
    {
        return DB::transaction(function () use ($asset, $destinationId, $actorId, $effectiveAt, $lockVersion, $type, $reason, $idempotencyKey) {
            $this->assertActor($asset, $actorId);
            if ($existing = $this->idempotent(AssetMutation::class, $asset, $idempotencyKey, ['destination_location_id' => $destinationId, 'mutation_type' => $type, 'reason' => $reason])) {
                return $existing;
            }
            $asset = $this->current($asset, $lockVersion);
            $mutation = AssetMutation::query()->create([
                'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id,
                'origin_location_id' => $asset->current_location_id, 'destination_location_id' => $destinationId,
                'mutation_type' => $type, 'reason' => $reason, 'effective_at' => $effectiveAt, 'status' => 'completed',
                'requested_by' => $actorId, 'executed_by' => $actorId, 'idempotency_key' => $idempotencyKey,
            ]);
            $this->updateProjection($asset, $lockVersion, ['current_location_id' => $destinationId, 'updated_by' => $actorId]);

            return $mutation;
        });
    }

    public function assignResponsibility(Asset $asset, int $responsiblePartyId, int $actorId, DateTimeInterface $effectiveAt, int $lockVersion, ?string $reference = null): AssetResponsibilityAssignment
    {
        return DB::transaction(function () use ($asset, $responsiblePartyId, $actorId, $effectiveAt, $lockVersion, $reference) {
            $this->assertActor($asset, $actorId);
            $asset = $this->current($asset, $lockVersion);
            AssetResponsibilityAssignment::query()->where('tenant_id', $asset->tenant_id)->where('asset_id', $asset->id)->whereNull('valid_until')->update(['valid_until' => $effectiveAt]);
            $assignment = AssetResponsibilityAssignment::query()->create([
                'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id, 'responsible_party_id' => $responsiblePartyId,
                'valid_from' => $effectiveAt, 'assignment_reference' => $reference, 'assigned_by' => $actorId,
            ]);
            $this->updateProjection($asset, $lockVersion, ['current_responsible_party_id' => $responsiblePartyId, 'updated_by' => $actorId]);

            return $assignment;
        });
    }

    public function changeCondition(Asset $asset, string $condition, int $actorId, DateTimeInterface $effectiveAt, int $lockVersion, string $source, ?string $reason = null, ?string $idempotencyKey = null): AssetConditionEvent
    {
        return DB::transaction(function () use ($asset, $condition, $actorId, $effectiveAt, $lockVersion, $source, $reason, $idempotencyKey) {
            $this->assertActor($asset, $actorId);
            if ($existing = $this->idempotent(AssetConditionEvent::class, $asset, $idempotencyKey, ['new_condition' => $condition, 'source_type' => $source, 'reason' => $reason])) {
                return $existing;
            }
            $asset = $this->current($asset, $lockVersion);
            $event = AssetConditionEvent::query()->create([
                'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id, 'previous_condition' => $asset->condition,
                'new_condition' => $condition, 'effective_at' => $effectiveAt, 'reason' => $reason,
                'source_type' => $source, 'actor_id' => $actorId, 'idempotency_key' => $idempotencyKey,
            ]);
            $this->updateProjection($asset, $lockVersion, ['condition' => $condition, 'updated_by' => $actorId]);

            return $event;
        });
    }

    public function transitionLifecycle(Asset $asset, string $status, int $actorId, DateTimeInterface $effectiveAt, int $lockVersion, string $type, ?string $reason = null, ?string $idempotencyKey = null): AssetLifecycleEvent
    {
        return DB::transaction(function () use ($asset, $status, $actorId, $effectiveAt, $lockVersion, $type, $reason, $idempotencyKey) {
            $this->assertActor($asset, $actorId);
            if ($existing = $this->idempotent(AssetLifecycleEvent::class, $asset, $idempotencyKey, ['to_status' => $status, 'transition_type' => $type, 'reason' => $reason])) {
                return $existing;
            }
            $asset = $this->current($asset, $lockVersion);
            $event = AssetLifecycleEvent::query()->create([
                'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id, 'from_status' => $asset->lifecycle_status,
                'to_status' => $status, 'transition_type' => $type, 'effective_at' => $effectiveAt,
                'reason' => $reason, 'actor_id' => $actorId, 'idempotency_key' => $idempotencyKey,
            ]);
            $this->updateProjection($asset, $lockVersion, ['lifecycle_status' => $status, 'updated_by' => $actorId]);

            return $event;
        });
    }

    /** @param array<string, mixed> $changes */
    public function correct(Asset $asset, array $changes, string $reason, int $actorId, DateTimeInterface $appliedAt, int $lockVersion, ?string $reference = null, ?string $idempotencyKey = null): AssetCorrection
    {
        return DB::transaction(function () use ($asset, $changes, $reason, $actorId, $appliedAt, $lockVersion, $reference, $idempotencyKey) {
            if (trim($reason) === '') {
                throw new InvalidArgumentException('A correction reason is required.');
            }
            $this->assertActor($asset, $actorId);
            $allowed = ['name', 'description', 'classification_id', 'current_location_id', 'current_responsible_party_id', 'condition', 'lifecycle_status'];
            if ($changes === [] || array_diff(array_keys($changes), $allowed)) {
                throw new InvalidArgumentException('Unsupported asset correction field.');
            }
            ksort($changes);
            if ($existing = $this->idempotent(AssetCorrection::class, $asset, $idempotencyKey, ['after_values' => $changes, 'reason' => $reason, 'reference' => $reference])) {
                return $existing;
            }
            $asset = $this->current($asset, $lockVersion);
            $before = [];
            foreach (array_keys($changes) as $field) {
                $before[$field] = $asset->getAttribute($field);
            }
            $fields = array_fill_keys(array_keys($changes), true);
            if (array_key_exists('condition', $changes)) {
                AssetConditionEvent::query()->create([
                    'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id, 'previous_condition' => $asset->condition,
                    'new_condition' => $changes['condition'], 'effective_at' => $appliedAt, 'reason' => $reason,
                    'source_type' => 'correction', 'actor_id' => $actorId,
                ]);
            }
            if (array_key_exists('classification_id', $changes)) {
                AssetClassificationAssignment::query()->where('tenant_id', $asset->tenant_id)->where('asset_id', $asset->id)->whereNull('valid_until')->update(['valid_until' => $appliedAt]);
                AssetClassificationAssignment::query()->create([
                    'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id,
                    'classification_id' => $changes['classification_id'], 'valid_from' => $appliedAt,
                    'assignment_type' => 'correction', 'reason' => $reason, 'assigned_by' => $actorId,
                ]);
            }
            if (array_key_exists('current_location_id', $changes)) {
                AssetMutation::query()->create([
                    'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id,
                    'origin_location_id' => $asset->current_location_id, 'destination_location_id' => $changes['current_location_id'],
                    'mutation_type' => 'correction', 'reason' => $reason, 'effective_at' => $appliedAt,
                    'status' => 'completed', 'requested_by' => $actorId, 'executed_by' => $actorId,
                ]);
            }
            if (array_key_exists('current_responsible_party_id', $changes)) {
                AssetResponsibilityAssignment::query()->where('tenant_id', $asset->tenant_id)->where('asset_id', $asset->id)->whereNull('valid_until')->update(['valid_until' => $appliedAt]);
                AssetResponsibilityAssignment::query()->create([
                    'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id,
                    'responsible_party_id' => $changes['current_responsible_party_id'], 'valid_from' => $appliedAt,
                    'assignment_reference' => $reference, 'assigned_by' => $actorId,
                ]);
            }
            if (array_key_exists('lifecycle_status', $changes)) {
                AssetLifecycleEvent::query()->create([
                    'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id,
                    'from_status' => $asset->lifecycle_status, 'to_status' => $changes['lifecycle_status'],
                    'transition_type' => 'correction', 'effective_at' => $appliedAt,
                    'reason' => $reason, 'actor_id' => $actorId,
                ]);
            }
            $correction = AssetCorrection::query()->create([
                'tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id, 'correction_type' => 'controlled',
                'corrected_fields' => $fields, 'before_values' => $before, 'after_values' => $changes,
                'reason' => $reason, 'reference' => $reference, 'applied_by' => $actorId,
                'applied_at' => $appliedAt, 'idempotency_key' => $idempotencyKey,
            ]);
            $this->updateProjection($asset, $lockVersion, $changes + ['updated_by' => $actorId]);

            return $correction;
        });
    }

    private function current(Asset $asset, int $lockVersion): Asset
    {
        $current = Asset::query()->whereKey($asset->id)->where('tenant_id', $asset->tenant_id)->lockForUpdate()->firstOrFail();
        if ($current->lock_version !== $lockVersion) {
            throw new RuntimeException('The asset was modified by another writer.');
        }

        return $current;
    }

    private function assertActor(Asset $asset, int $actorId): void
    {
        if (! Tenant::query()->whereKey($asset->tenant_id)->where('status', 'active')->exists() || ! $this->memberships->exists($actorId, $asset->tenant_id)) {
            throw new AuthorizationException('Active tenant membership is required.');
        }
    }

    /** @param array<string, mixed> $values */
    private function updateProjection(Asset $asset, int $lockVersion, array $values): void
    {
        DB::select("SELECT set_config('desatara.asset_history_write', 'on', true)");
        $updated = Asset::query()->whereKey($asset->id)->where('tenant_id', $asset->tenant_id)->where('lock_version', $lockVersion)
            ->update($values + ['lock_version' => $lockVersion + 1]);
        DB::select("SELECT set_config('desatara.asset_history_write', 'off', true)");
        if ($updated !== 1) {
            throw new RuntimeException('The asset was modified by another writer.');
        }
        $asset->refresh();
    }

    /** @template T of Model
     * @param  class-string<T>  $model
     * @param  array<string, mixed>  $expected
     * @return T|null
     */
    private function idempotent(string $model, Asset $asset, ?string $key, array $expected): ?Model
    {
        if ($key === null) {
            return null;
        }

        $existing = $model::query()->where('tenant_id', $asset->tenant_id)->where('idempotency_key', $key)->first();
        if (! $existing) {
            return null;
        }
        if ((int) $existing->getAttribute('asset_id') !== $asset->id) {
            throw new RuntimeException('Idempotency key belongs to another asset.');
        }
        foreach ($expected as $field => $value) {
            if ($existing->getAttribute($field) != $value) {
                throw new RuntimeException('Idempotency key was reused with different input.');
            }
        }

        return $existing;
    }
}
