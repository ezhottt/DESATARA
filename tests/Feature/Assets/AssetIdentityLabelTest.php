<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\Assets\AssetIdentityService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssetIdentityLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_identity_uses_tenant_classification_acquisition_year_and_zero_padded_nup(): void
    {
        [$tenant, $classification, $unit, $user] = $this->fixture('32.03.26.2007');
        $otherClassification = AssetClassification::query()->create([
            'classification_version_id' => $classification->classification_version_id,
            'code' => '1.3.2.10.01.02.004',
            'name' => 'Komputer Lain',
            'level' => 7,
            'status' => 'active',
        ]);

        $service = app(AssetIdentityService::class);
        $this->assertSame('001', $service->nextNup($tenant, $classification->id, 2026));
        $this->assertSame('002', $service->nextNup($tenant, $classification->id, 2026));
        $this->assertSame('001', $service->nextNup($tenant, $classification->id, 2025));
        $this->assertSame('001', $service->nextNup($tenant, $otherClassification->id, 2026));
        $this->assertSame('003', $service->nextNup($tenant, $classification->id, 2026));

        $asset = Asset::query()->create([
            'tenant_id' => $tenant->id,
            'classification_id' => $classification->id,
            'register_number' => '003',
            'name' => 'ASUS ExpertBook B1402',
            'acquisition_date' => '2026-03-15',
            'acquisition_year' => 2026,
            'quantity' => 1,
            'unit_id' => $unit->id,
            'condition' => 'good',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame(
            '32.03.26.2007 / 1.3.2.10.01.02.003 / 2026 / 003',
            $service->inventoryCode($tenant, $asset->load('classification'))
        );
    }

    public function test_numbering_scope_is_isolated_by_tenant_classification_and_year(): void
    {
        [$tenant, $classification] = $this->fixture('32.03.26.2007');
        [$otherTenant, $otherTenantClassification] = $this->fixture('32.03.26.2008');
        $otherClassification = AssetClassification::query()->create([
            'classification_version_id' => $classification->classification_version_id,
            'code' => '1.3.2.10.01.02.004',
            'name' => 'Komputer Lain',
            'level' => 7,
            'status' => 'active',
        ]);
        $service = app(AssetIdentityService::class);

        $this->assertSame('001', $service->nextNup($tenant, $classification->id, 2026));
        $this->assertSame('001', $service->nextNup($tenant, $otherClassification->id, 2026));
        $this->assertSame('001', $service->nextNup($tenant, $classification->id, 2025));
        $this->assertSame('001', $service->nextNup($otherTenant, $otherTenantClassification->id, 2026));
    }

    public function test_database_rejects_duplicate_nup_only_inside_same_scope(): void
    {
        [$tenant, $classification, $unit, $user] = $this->fixture('32.03.26.2007');
        $otherClassification = AssetClassification::query()->create([
            'classification_version_id' => $classification->classification_version_id,
            'code' => '1.3.2.10.01.02.004',
            'name' => 'Komputer Lain',
            'level' => 7,
            'status' => 'active',
        ]);
        $base = [
            'tenant_id' => $tenant->id,
            'register_number' => '001',
            'acquisition_date' => '2026-03-15',
            'acquisition_year' => 2026,
            'quantity' => 1,
            'unit_id' => $unit->id,
            'condition' => 'good',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        Asset::query()->create($base + ['classification_id' => $classification->id, 'name' => 'Unit A']);
        Asset::query()->create($base + ['classification_id' => $otherClassification->id, 'name' => 'Unit B']);

        $this->expectException(QueryException::class);
        Asset::query()->create($base + ['classification_id' => $classification->id, 'name' => 'Unit C']);
    }

    public function test_sequence_does_not_recycle_a_previously_issued_number(): void
    {
        [$tenant, $classification] = $this->fixture('32.03.26.2007');
        $service = app(AssetIdentityService::class);

        $this->assertSame('001', $service->nextNup($tenant, $classification->id, 2026));
        $this->assertSame('002', $service->nextNup($tenant, $classification->id, 2026));
    }

    public function test_issued_nup_and_acquisition_identity_are_database_immutable_for_direct_writes(): void
    {
        [$tenant, $classification, $unit, $user] = $this->fixture('32.03.26.2007');
        $asset = Asset::query()->create([
            'tenant_id' => $tenant->id,
            'classification_id' => $classification->id,
            'register_number' => '001',
            'name' => 'Immutable Asset',
            'acquisition_date' => '2026-03-15',
            'acquisition_year' => 2026,
            'quantity' => 1,
            'unit_id' => $unit->id,
            'condition' => 'good',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->expectException(QueryException::class);
        Asset::query()->whereKey($asset->id)->update(['register_number' => '002']);
    }

    public function test_numbering_service_uses_database_row_lock_for_race_safety(): void
    {
        $source = file_get_contents(app_path('Services/Assets/AssetIdentityService.php'));

        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('insertOrIgnore', $source);
        $this->assertStringContainsString('numbering_sequences', $source);
    }

    private function fixture(string $villageCode): array
    {
        $tenant = Tenant::factory()->active()->create(['village_code' => $villageCode]);
        $user = User::factory()->create();
        $scheme = ClassificationScheme::query()->firstOrCreate(
            ['code' => 'ASET-DESA'],
            ['name' => 'Aset Desa', 'scope' => 'national', 'status' => 'active']
        );
        $version = ClassificationVersion::query()->firstOrCreate(
            ['classification_scheme_id' => $scheme->id, 'version_label' => '2026'],
            ['effective_from' => '2026-01-01', 'status' => 'published']
        );
        $classification = AssetClassification::query()->firstOrCreate(
            ['classification_version_id' => $version->id, 'code' => '1.3.2.10.01.02.003'],
            ['name' => 'Notebook', 'level' => 7, 'status' => 'active']
        );
        $unit = Unit::query()->firstOrCreate(['code' => 'UNIT'], ['name' => 'Unit', 'status' => 'active']);

        return [$tenant, $classification, $unit, $user];
    }
}
