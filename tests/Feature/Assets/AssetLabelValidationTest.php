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
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AssetLabelValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_uses_official_region_master_item_acquisition_date_and_stored_register(): void
    {
        [$tenant, $asset] = $this->fixture(
            ['name' => 'Desa Cikadu Demo', 'village_code' => '32.03.26.2007'],
            ['register_number' => '3', 'acquisition_date' => '2021-08-10', 'acquisition_year' => 1999],
            ['code' => '1.3.2.10.01.02.003', 'name' => 'Peralatan Mesin', 'level' => 7]
        );

        $payload = app(AssetIdentityService::class)->labelPayload($tenant, $asset);

        $this->assertSame('32.03.26.2007', $payload['village_code']);
        $this->assertSame('1.3.2.10.01.02.003', $payload['item_code']);
        $this->assertSame(2021, $payload['acquisition_year']);
        $this->assertSame('003', $payload['register_number']);
        $this->assertSame('32.03.26.2007 / 1.3.2.10.01.02.003 / 2021 / 003', $payload['inventory_code']);
        $this->assertSame('CIKADU', $payload['village_name']);
    }

    public function test_label_rejects_missing_village_code(): void
    {
        [$tenant, $asset] = $this->fixture(['village_code' => null]);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'Label belum dapat dicetak karena Kode Wilayah Desa belum tersedia.');
    }

    public function test_label_rejects_slug_instead_of_official_village_code(): void
    {
        [$tenant, $asset] = $this->fixture(['village_code' => 'DEMO-CIKADU']);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'Label belum dapat dicetak karena Kode Wilayah Desa belum tersedia.');
    }

    public function test_label_rejects_missing_item_code(): void
    {
        [$tenant, $asset] = $this->fixture([], [], ['code' => '']);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'Label belum dapat dicetak karena Kode Barang belum tersedia.');
    }

    public function test_label_rejects_category_name_instead_of_official_item_code(): void
    {
        [$tenant, $asset] = $this->fixture([], [], ['code' => 'PERALATAN', 'name' => 'Peralatan dan Mesin', 'level' => 1]);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'Label belum dapat dicetak karena Kode Barang belum tersedia.');
    }

    public function test_label_rejects_missing_register_number(): void
    {
        [$tenant, $asset] = $this->fixture([], ['register_number' => null]);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'Label belum dapat dicetak karena Nomor Register belum tersedia.');
    }

    public function test_label_rejects_non_numeric_register_number(): void
    {
        [$tenant, $asset] = $this->fixture([], ['register_number' => 'REG/2026/001']);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'Label belum dapat dicetak karena Nomor Register tidak valid.');
    }

    public function test_label_rejects_missing_acquisition_date_as_missing_acquisition_year(): void
    {
        [$tenant, $asset] = $this->fixture([], ['acquisition_date' => null, 'acquisition_year' => 2026]);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'Label belum dapat dicetak karena Tahun Perolehan belum tersedia.');
    }

    public function test_label_rejects_missing_asset_name(): void
    {
        [$tenant, $asset] = $this->fixture([], ['name' => '']);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'Label belum dapat dicetak karena Nama Barang belum tersedia.');
    }

    public function test_label_rejects_aggregate_quantity(): void
    {
        [$tenant, $asset] = $this->fixture([], ['quantity' => 2]);
        $this->assertValidationContains(fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset), 'jumlah 1 unit');
    }

    private function assertValidationContains(Closure $callback, string $fragment): void
    {
        try {
            $callback();
            $this->fail('Expected label validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($fragment, implode(' ', $exception->errors()['asset'] ?? []));
        }
    }

    private function fixture(array $tenantOverrides = [], array $assetOverrides = [], array $classificationOverrides = []): array
    {
        $tenant = Tenant::factory()->active()->create(array_replace([
            'name' => 'Desa Cikadu',
            'village_code' => '32.03.26.2007',
        ], $tenantOverrides));
        $user = User::factory()->create();
        $scheme = ClassificationScheme::query()->create(['code' => uniqid('scheme-'), 'name' => 'Aset Desa', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '2026', 'effective_from' => '2026-01-01', 'status' => 'published']);
        $classification = AssetClassification::query()->create(array_replace([
            'classification_version_id' => $version->id,
            'code' => '1.3.2.10.01.02.003',
            'name' => 'Notebook',
            'level' => 7,
            'status' => 'active',
        ], $classificationOverrides));
        $unit = Unit::query()->create(['code' => uniqid('UNIT-'), 'name' => 'Unit', 'status' => 'active']);
        $asset = Asset::query()->create(array_replace([
            'tenant_id' => $tenant->id,
            'classification_id' => $classification->id,
            'register_number' => '001',
            'name' => 'Notebook Test',
            'acquisition_date' => '2026-03-15',
            'acquisition_year' => 2026,
            'quantity' => 1,
            'unit_id' => $unit->id,
            'condition' => 'good',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $assetOverrides))->load('classification');

        return [$tenant, $asset];
    }
}
