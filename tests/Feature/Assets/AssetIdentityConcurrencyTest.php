<?php

namespace Tests\Feature\Assets;

use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Tenant;
use App\Services\Assets\AssetIdentityService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Concurrency;
use Tests\TestCase;

final class AssetIdentityConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_nup_generation_never_returns_duplicate_in_same_scope(): void
    {
        $tenant = Tenant::factory()->active()->create(['village_code' => '32.03.26.2007']);
        $scheme = ClassificationScheme::query()->create(['code' => 'ASET-RACE', 'name' => 'Aset Race', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '2026', 'effective_from' => '2026-01-01', 'status' => 'published']);
        $classification = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => '1.3.2.10.01.02.003', 'name' => 'Notebook', 'level' => 7, 'status' => 'active']);
        $tenantId = $tenant->id;
        $classificationId = $classification->id;

        $results = Concurrency::driver('process')->run([
            fn () => app(AssetIdentityService::class)->nextNup(Tenant::query()->findOrFail($tenantId), $classificationId, 2026),
            fn () => app(AssetIdentityService::class)->nextNup(Tenant::query()->findOrFail($tenantId), $classificationId, 2026),
        ], 20);

        sort($results);
        $this->assertSame(['001', '002'], array_values($results));
        $this->assertSame(2, count(array_unique($results)));
    }
}
