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

    public function test_label_rejects_missing_village_code(): void
    {
        [$tenant, $asset] = $this->fixture(['village_code' => null]);

        $this->assertValidationContains(
            fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset),
            'Kode Wilayah Desa'
        );
    }

    public function test_label_rejects_missing_item_code(): void
    {
        [$tenant, $asset] = $this->fixture([], [], ['code' => '']);

        $this->assertValidationContains(
            fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset),
            'Kode Barang'
        );
    }

    public function test_label_rejects_missing_nup(): void
    {
        [$tenant, $asset] = $this->fixture([], ['register_number' => null]);

        $this->assertValidationContains(
            fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset),
            'NUP/Register belum dibuat'
        );
    }

    public function test_label_rejects_missing_acquisition_date(): void
    {
        [$tenant, $asset] = $this->fixture([], ['acquisition_date' => null, 'acquisition_year' => 2026]);

        $this->assertValidationContains(
            fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset),
            'Tanggal Perolehan'
        );
    }

    public function test_label_rejects_year_that_disagrees_with_acquisition_date(): void
    {
        [$tenant, $asset] = $this->fixture([], ['acquisition_date' => '2026-03-15', 'acquisition_year' => 2025]);

        $this->assertValidationContains(
            fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset),
            'tidak konsisten'
        );
    }

    public function test_label_rejects_aggregate_quantity(): void
    {
        [$tenant, $asset] = $this->fixture([], ['quantity' => 2]);

        $this->assertValidationContains(
            fn () => app(AssetIdentityService::class)->labelPayload($tenant, $asset),
            'jumlah 1 unit'
        );
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
        $tenant = Tenant::factory()->active()->create(array_replace(['village_code' => '32.03.26.2007'], $tenantOverrides));
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
