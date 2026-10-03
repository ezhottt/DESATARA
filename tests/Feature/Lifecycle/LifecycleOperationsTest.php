<?php

namespace Tests\Feature\Lifecycle;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AssetLocation;
use App\Models\AssetValuation;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use App\Services\Lifecycle\ManageAssetLifecycleOperations;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LifecycleOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_completion_is_a_historical_operation(): void
    {
        [$tenant, $user, $asset] = $this->fixture();
        $service = app(ManageAssetLifecycleOperations::class);
        $maintenance = $service->createMaintenance($tenant, $user, $asset, [
            'maintenance_type' => 'repair',
            'planned_at' => now()->toDateString(),
            'planned_cost' => '100.00',
        ]);

        $service->completeMaintenance($tenant, $user, $maintenance, [
            'actual_cost' => '125.50',
            'condition_after' => 'good',
            'notes' => 'Completed',
        ]);

        $this->assertSame('completed', $maintenance->fresh()->status);
        $this->assertSame('125.50', $maintenance->fresh()->actual_cost);
    }

    public function test_valuation_is_append_only_and_transfer_execution_uses_domain_history(): void
    {
        [$tenant, $user, $asset] = $this->fixture();
        $destination = AssetLocation::query()->create(['tenant_id' => $tenant->id, 'code' => 'L2', 'name' => 'Archive', 'location_type' => 'room', 'status' => 'active']);
        $service = app(ManageAssetLifecycleOperations::class);

        $first = $service->recordValuation($tenant, $user, $asset, ['purpose' => 'insurance', 'valuation_date' => today(), 'amount' => '100.00', 'valuer_name' => 'Official']);
        $second = $service->recordValuation($tenant, $user, $asset, ['purpose' => 'insurance', 'valuation_date' => today(), 'amount' => '125.00', 'valuer_name' => 'Official']);
        $transfer = $service->createTransfer($tenant, $user, 'EXCHANGE', [$asset]);
        $service->executeTransfer($tenant, $user, $transfer, ['idempotency_key' => 'transfer-1', 'destination_location_id' => $destination->id]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, AssetValuation::query()->where('asset_id', $asset->id)->count());
        $this->assertSame('transferred', $asset->fresh()->lifecycle_status);
        $this->assertSame($destination->id, $asset->fresh()->current_location_id);
        $this->assertSame('executed', $transfer->fresh()->status);
    }

    public function test_transfer_rejects_cross_tenant_asset_and_does_not_write(): void
    {
        [$tenant, $user, $asset] = $this->fixture();
        $other = Tenant::factory()->active()->create();
        $otherUser = User::factory()->create();
        TenantMembership::factory()->active()->create(['tenant_id' => $other->id, 'user_id' => $otherUser->id]);

        $this->expectException(AuthorizationException::class);
        app(ManageAssetLifecycleOperations::class)->createTransfer($other, $otherUser, 'SALE', [$asset]);

        $this->assertSame(0, DB::table('asset_transfers')->count());
    }

    /** @return array{0: Tenant, 1: User, 2: Asset} */
    private function fixture(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        TenantMembership::factory()->active()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);
        $scheme = ClassificationScheme::query()->create(['code' => uniqid('S'), 'name' => 'Scheme', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $classification = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => uniqid('C'), 'name' => 'General', 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->create(['code' => uniqid('U'), 'name' => 'Unit', 'status' => 'active']);
        $asset = Asset::query()->create(['tenant_id' => $tenant->id, 'classification_id' => $classification->id, 'name' => 'Asset', 'quantity' => 1, 'unit_id' => $unit->id, 'condition' => 'good', 'lifecycle_status' => 'active', 'verification_status' => 'verified', 'created_by' => $user->id, 'updated_by' => $user->id]);

        return [$tenant, $user, $asset];
    }
}
