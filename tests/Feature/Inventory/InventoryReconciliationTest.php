<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AssetLocation;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use App\Services\Inventory\ManageInventory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class InventoryReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_freeze_creates_historical_expected_snapshot_and_observation_does_not_mutate_asset(): void
    {
        [$tenant, $user, $asset, $location] = $this->assetContext();
        $service = app(ManageInventory::class);
        $session = $service->createSession($tenant, $user, 'Inventory 2026', '2026-S2', [], Carbon::parse('2026-10-01'));

        $service->freezeDataset($session);
        $item = $session->refresh()->items()->firstOrFail();
        $asset->update(['name' => 'Changed master']);

        $this->assertSame('prepared', $session->refresh()->status);
        $this->assertSame('Synthetic Asset', $item->refresh()->asset_name_snapshot);
        $this->assertSame($location->id, $item->expected_location_id);

        $service->observeItem($session, $item, $user, ['result' => 'relocated', 'observed_location_id' => $location->id]);
        $this->assertSame('Changed master', $asset->refresh()->name);
        $this->assertDatabaseHas('inventory_discrepancies', ['inventory_item_id' => $item->id, 'discrepancy_type' => 'relocated']);
    }

    public function test_cross_tenant_assets_cannot_be_frozen_or_referenced(): void
    {
        [$tenant, $user] = $this->memberContext();
        [$other, $otherUser, $asset] = $this->assetContext();
        $session = app(ManageInventory::class)->createSession($tenant, $user, 'Inventory', '2026', [], now());

        $this->expectException(QueryException::class);
        $session->items()->create(['tenant_id' => $tenant->id, 'asset_id' => $asset->id, 'asset_uuid_snapshot' => $asset->uuid, 'asset_code_snapshot' => null, 'register_number_snapshot' => null, 'asset_name_snapshot' => $asset->name, 'classification_id_snapshot' => $asset->classification_id, 'classification_code_snapshot' => '1', 'classification_name_snapshot' => 'Asset', 'lifecycle_status_snapshot' => 'draft', 'verification_result' => 'not_checked', 'snapshot_payload' => []]);
    }

    public function test_discovery_is_not_an_asset_and_reconciliation_is_idempotent_controlled_history(): void
    {
        [$tenant, $user] = $this->memberContext();
        $service = app(ManageInventory::class);
        $session = $service->createSession($tenant, $user, 'Inventory', '2026', [], now());
        $discovery = $service->createDiscovery($session, $user, 'TEMP-1', 'Unknown object');

        $service->resolveDiscovery($discovery, $user, 'unidentified');

        $this->assertSame('unidentified', $discovery->refresh()->resolution_status);
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_finalization_requires_resolved_discrepancies_and_then_is_immutable(): void
    {
        [$tenant, $user, $asset] = $this->assetContext();
        $service = app(ManageInventory::class);
        $session = $service->createSession($tenant, $user, 'Inventory', '2026', [], now());
        $service->freezeDataset($session);
        $item = $session->refresh()->items()->firstOrFail();
        $service->observeItem($session, $item, $user, ['result' => 'missing']);

        $this->expectException(RuntimeException::class);
        $service->finalize($session, $user);
    }

    public function test_reconciliation_is_controlled_idempotent_and_allows_finalization(): void
    {
        [$tenant, $user] = $this->assetContext();
        $service = app(ManageInventory::class);
        $session = $service->createSession($tenant, $user, 'Inventory', '2026', [], now());
        $service->freezeDataset($session);
        $item = $session->refresh()->items()->firstOrFail();
        $service->observeItem($session, $item, $user, ['result' => 'missing']);
        $discrepancy = $session->refresh()->discrepancies()->firstOrFail();

        $reconciliation = $service->reconcile($discrepancy, $user, 'no_change', 'Verified against supporting evidence.', 'reconcile-1');

        $this->assertSame('reconciled', $discrepancy->refresh()->status);
        $this->assertSame($reconciliation->id, $service->reconcile($discrepancy, $user, 'no_change', 'Verified against supporting evidence.', 'reconcile-1')->id);
        $this->assertSame('finalized', $service->finalize($session, $user)->status);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'inventory.session.finalized']);
    }

    public function test_finalized_session_cannot_be_observed_or_finalization_repeated(): void
    {
        [$tenant, $user, $asset] = $this->assetContext();
        $service = app(ManageInventory::class);
        $session = $service->createSession($tenant, $user, 'Inventory', '2026', [], now());
        $service->freezeDataset($session);
        $item = $session->refresh()->items()->firstOrFail();
        $service->observeItem($session, $item, $user, ['result' => 'matched']);
        $service->finalize($session, $user);

        $this->assertSame('finalized', $session->refresh()->status);
        $this->assertSame($session->id, $service->finalize($session, $user)->id);
        $this->expectException(RuntimeException::class);
        $service->observeItem($session, $item, $user, ['result' => 'matched']);
    }

    private function memberContext(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return [$tenant, $user];
    }

    private function assetContext(): array
    {
        [$tenant, $user] = $this->memberContext();
        $scheme = ClassificationScheme::query()->create(['code' => fake()->unique()->lexify('S????'), 'name' => 'Scheme', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $class = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => '1', 'name' => 'Asset', 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->create(['code' => fake()->unique()->lexify('U????'), 'name' => 'Unit', 'status' => 'active']);
        $location = AssetLocation::query()->create(['tenant_id' => $tenant->id, 'code' => fake()->unique()->lexify('L????'), 'name' => 'Room', 'location_type' => 'room', 'status' => 'active']);
        $asset = Asset::query()->create(['tenant_id' => $tenant->id, 'classification_id' => $class->id, 'asset_code' => 'A-1', 'name' => 'Synthetic Asset', 'quantity' => 1, 'unit_id' => $unit->id, 'current_location_id' => $location->id, 'condition' => 'good', 'created_by' => $user->id, 'updated_by' => $user->id]);

        return [$tenant, $user, $asset, $location];
    }
}
