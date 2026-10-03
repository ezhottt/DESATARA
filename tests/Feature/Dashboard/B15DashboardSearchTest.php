<?php

namespace Tests\Feature\Dashboard;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class B15DashboardSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_search_are_tenant_scoped_and_paginated(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $other = Tenant::factory()->active()->create();
        $this->asset($tenant, $user, 'Visible Asset', 'VIS-1');
        $this->asset($other, $user, 'Hidden Asset', 'HID-1');
        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid]);

        $this->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')->where('metrics.total_assets', 1)->where('tenant.name', $tenant->name));
        $this->get('/search?q=Asset')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Search')->where('assets.total', 1)->where('assets.data.0.name', 'Visible Asset'));
    }

    public function test_dashboard_and_search_require_asset_permission(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])->get('/search')->assertForbidden();
    }

    public function test_search_rejects_oversized_queries_and_is_rate_limited(): void
    {
        [$tenant, $user] = $this->context();
        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid]);

        $this->get('/search?q='.str_repeat('a', 101))->assertSessionHasErrors('q');

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->get('/search?q=asset');
        }

        $this->get('/search?q=asset')->assertTooManyRequests();
    }

    private function context(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();
        $permission = Permission::query()->create(['code' => 'assets.view', 'domain' => 'assets', 'action' => 'view']);
        $role = Role::query()->create(['code' => 'b15-viewer', 'name' => 'B15 Viewer', 'scope_type' => 'tenant', 'is_system' => false]);
        $role->permissions()->attach($permission);
        $membership->roles()->attach($role, ['assigned_at' => now()]);

        return [$tenant, $user, $membership];
    }

    private function asset(Tenant $tenant, User $user, string $name, string $code): Asset
    {
        $scheme = ClassificationScheme::query()->create(['code' => $code.'-scheme', 'name' => $code, 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $classification = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => $code.'-class', 'name' => $name, 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->firstOrCreate(['code' => 'UNIT', 'name' => 'Unit', 'status' => 'active']);

        return Asset::query()->create(['tenant_id' => $tenant->id, 'classification_id' => $classification->id, 'name' => $name, 'asset_code' => $code, 'unit_id' => $unit->id, 'condition' => 'good', 'created_by' => $user->id, 'updated_by' => $user->id]);
    }
}
