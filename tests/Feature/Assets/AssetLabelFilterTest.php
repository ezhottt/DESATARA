<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AssetLabelFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtered_bulk_print_is_resolved_server_side_inside_active_tenant(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('village_code', 'DEMO-CIKADU')->firstOrFail();
        $classification = AssetClassification::query()->where('status', 'active')->firstOrFail();
        $unit = Unit::query()->where('status', 'active')->firstOrFail();

        Asset::query()->create([
            'tenant_id' => $tenant->id,
            'classification_id' => $classification->id,
            'register_number' => '001',
            'name' => 'FILTER-ONLY-ASSET-XYZ',
            'acquisition_date' => '2030-03-15',
            'acquisition_year' => 2030,
            'quantity' => 1,
            'unit_id' => $unit->id,
            'condition' => 'good',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $otherTenant = Tenant::factory()->active()->create(['village_code' => 'OTHER-VILLAGE']);
        Asset::query()->create([
            'tenant_id' => $otherTenant->id,
            'classification_id' => $classification->id,
            'register_number' => '001',
            'name' => 'FILTER-ONLY-ASSET-XYZ',
            'acquisition_date' => '2030-03-15',
            'acquisition_year' => 2030,
            'quantity' => 1,
            'unit_id' => $unit->id,
            'condition' => 'good',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets/labels/prepare', ['filter_q' => 'FILTER-ONLY-ASSET-XYZ'])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assets/Labels')
                ->has('labels', 1)
                ->where('labels.0.village_name', $tenant->name)
                ->where('labels.0.name', 'FILTER-ONLY-ASSET-XYZ')
            );
    }
}
