<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AssetLocation;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_rejects_cross_tenant_location(): void
    {
        [$assetData,$tenant] = $this->base();
        $other = Tenant::factory()->active()->create();
        $location = AssetLocation::query()->create(['tenant_id' => $other->id, 'code' => 'X', 'name' => 'Other', 'location_type' => 'room', 'status' => 'active']);
        $this->expectException(QueryException::class);
        Asset::query()->create($assetData + ['tenant_id' => $tenant->id, 'current_location_id' => $location->id]);
    }

    public function test_asset_requires_positive_quantity_and_non_negative_value(): void
    {
        [$data,$tenant] = $this->base();
        $this->expectException(QueryException::class);
        Asset::query()->create($data + ['tenant_id' => $tenant->id, 'quantity' => 0, 'acquisition_value' => -1]);
    }

    public function test_asset_registration_has_uuid_lock_and_append_acquisition(): void
    {
        [$data,$tenant] = $this->base();
        $asset = Asset::query()->create($data + ['tenant_id' => $tenant->id]);
        $asset->acquisitions()->create(['tenant_id' => $tenant->id, 'acquisition_type' => 'purchase', 'source' => 'synthetic', 'quantity' => 1, 'total_value' => 1000, 'created_by' => $data['created_by']]);
        $this->assertNotNull($asset->uuid);
        $this->assertSame(1, $asset->lock_version);
        $this->assertCount(1, $asset->acquisitions);
    }

    private function base(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $scheme = ClassificationScheme::query()->create(['code' => 'S', 'name' => 'S', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $class = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => '1', 'name' => 'Asset', 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->create(['code' => 'UNIT', 'name' => 'Unit', 'status' => 'active']);

        return [['classification_id' => $class->id, 'name' => 'Synthetic Asset', 'quantity' => 1, 'unit_id' => $unit->id, 'condition' => 'good', 'lifecycle_status' => 'draft', 'verification_status' => 'unverified', 'created_by' => $user->id, 'updated_by' => $user->id], $tenant];
    }
}
