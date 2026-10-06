<?php

namespace Tests\Feature\Seeders;

use App\Models\Asset;
use App\Models\AssetReport;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_a_repeatable_local_dataset_with_label_ready_representative_asset(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $tenant = Tenant::query()->where('uuid', '00000000-0000-4000-8000-000000000001')->firstOrFail();

        $this->assertSame('Desa Cikadu', $tenant->name);
        $this->assertSame('32.03.26.2002', $tenant->village_code);
        $this->assertSame(1, Tenant::query()->where('village_code', '32.03.26.2002')->count());
        $this->assertSame(0, Tenant::query()->where('village_code', 'DEMO-CIKADU')->count());
        $this->assertSame(1, User::query()->where('email', 'admin@demo.desatara.local')->count());
        $this->assertSame(40, Asset::query()->where('tenant_id', $tenant->id)->count());
        $this->assertGreaterThan(1, Asset::query()->where('tenant_id', $tenant->id)->distinct()->count('condition'));
        $this->assertGreaterThan(1, Asset::query()->where('tenant_id', $tenant->id)->distinct()->count('current_location_id'));

        $labelAsset = Asset::query()
            ->with('classification')
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Mesin Potong Rumput')
            ->firstOrFail();

        $this->assertSame('1.3.2.10.01.02.003', $labelAsset->classification?->code);
        $this->assertSame(7, (int) $labelAsset->classification?->level);
        $this->assertSame('003', $labelAsset->register_number);
        $this->assertSame('2021-07-15', (string) $labelAsset->acquisition_date);
    }

    public function test_demo_seeder_does_not_mutate_finalized_demo_report_on_reseed(): void
    {
        $this->seed(DemoSeeder::class);

        $report = AssetReport::query()->firstOrFail();
        $report->forceFill([
            'status' => 'finalized',
            'finalized_at' => now(),
        ])->save();

        $this->seed(DemoSeeder::class);

        $this->assertSame('finalized', $report->fresh()->status);
        $this->assertNotNull($report->fresh()->finalized_at);
    }

    public function test_demo_seeder_refuses_non_local_environments(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DemoSeeder hanya boleh dijalankan pada environment local atau testing.');

        (new DemoSeeder)->run();
    }
}
