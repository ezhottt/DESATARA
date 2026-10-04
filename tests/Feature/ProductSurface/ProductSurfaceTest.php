<?php

namespace Tests\Feature\ProductSurface;

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

class ProductSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_surface_is_permissioned_and_tenant_scoped(): void
    {
        [$tenant, $user, $membership] = $this->member();
        $this->grant($membership, 'assets.view');
        $this->asset($tenant, $user, 'Visible');
        $other = Tenant::factory()->active()->create();
        $this->asset($other, $user, 'Hidden');

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])->get('/assets')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Surface/Index')->where('items.total', 1)->where('items.data.0.name', 'Visible'));
    }

    public function test_surface_denies_direct_access_without_tenant_permission(): void
    {
        [$tenant, $user] = $this->member();

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])->get('/assets')->assertForbidden();
    }

    private function member(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return [$tenant, $user, $membership];
    }

    private function grant(TenantMembership $membership, string $code): void
    {
        $permission = Permission::query()->create(['code' => $code, 'domain' => 'assets', 'action' => 'view']);
        $role = Role::query()->create(['code' => 'surface-'.uniqid(), 'name' => 'Surface role', 'scope_type' => 'tenant', 'is_system' => false]);
        $role->permissions()->attach($permission);
        $membership->roles()->attach($role, ['assigned_at' => now()]);
    }

    private function asset(Tenant $tenant, User $user, string $name): Asset
    {
        $scheme = ClassificationScheme::query()->create(['code' => uniqid('scheme-'), 'name' => 'Scheme', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $classification = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => uniqid('class-'), 'name' => 'General', 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->create(['code' => uniqid('unit-'), 'name' => 'Unit', 'status' => 'active']);

        return Asset::query()->create(['tenant_id' => $tenant->id, 'classification_id' => $classification->id, 'name' => $name, 'quantity' => 1, 'unit_id' => $unit->id, 'condition' => 'good', 'created_by' => $user->id, 'updated_by' => $user->id]);
    }
}
