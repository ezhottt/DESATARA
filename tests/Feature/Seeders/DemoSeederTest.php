<?php

namespace Tests\Feature\Seeders;

use App\Models\Asset;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_a_repeatable_local_dataset(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $tenant = Tenant::query()->where('village_code', 'DEMO-CIKADU')->firstOrFail();

        $this->assertSame('Desa Cikadu Demo', $tenant->name);
        $this->assertSame(1, Tenant::query()->where('village_code', 'DEMO-CIKADU')->count());
        $this->assertSame(1, User::query()->where('email', 'admin@demo.desatara.local')->count());
        $this->assertSame(40, Asset::query()->where('tenant_id', $tenant->id)->count());
        $this->assertGreaterThan(1, Asset::query()->where('tenant_id', $tenant->id)->distinct()->count('condition'));
        $this->assertGreaterThan(1, Asset::query()->where('tenant_id', $tenant->id)->distinct()->count('current_location_id'));
    }

    public function test_demo_seeder_refuses_non_local_environments(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DemoSeeder hanya boleh dijalankan pada environment local atau testing.');

        (new DemoSeeder)->run();
    }
}
