<?php

namespace Tests\Feature\ProductSurface;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AssetLocation;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class B18B25ProductSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_surface_mutation_routes_are_registered(): void
    {
        foreach (['assets.store', 'assets.update', 'inventory.store', 'approvals.action', 'reports.action', 'interoperability.export', 'master-data.store'] as $name) {
            $this->assertNotNull(Route::getRoutes()->getByName($name), $name);
        }
    }

    public function test_mutation_endpoints_require_authentication(): void
    {
        $this->post('/inventory', [
            'name' => 'Inventory',
            'period_label' => '2026',
            'reference_at' => '2026-10-04',
        ])->assertRedirect('/login');

        $this->post('/interoperability/export')->assertRedirect('/login');
    }

    public function test_dashboard_remains_usable_for_a_member_without_asset_permission(): void
    {
        [$tenant, $user] = $this->member();

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard')->where('metrics', []));
    }

    public function test_shared_permissions_are_available_to_the_product_navigation(): void
    {
        [$tenant, $user, $membership] = $this->member();
        $this->grant($membership, 'assets.view');
        $this->grant($membership, 'inventory.execute');

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissions', fn ($permissions) => $permissions['assets.view'] === true && $permissions['inventory.execute'] === true)
            );
    }

    public function test_master_data_is_scoped_to_active_tenant(): void
    {
        [$tenant, $user, $membership] = $this->member();
        $this->grant($membership, 'tenant.settings.update');
        $own = AssetLocation::query()->create(['tenant_id' => $tenant->id, 'code' => 'OWN', 'name' => 'Own location', 'location_type' => 'room', 'status' => 'active']);
        $other = Tenant::factory()->active()->create();
        AssetLocation::query()->create(['tenant_id' => $other->id, 'code' => 'OTHER', 'name' => 'Other location', 'location_type' => 'room', 'status' => 'active']);

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/master-data/locations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('items.total', 1)->where('items.data.0.id', $own->id));
    }

    public function test_administration_surfaces_require_their_own_permission(): void
    {
        [$tenant, $user, $membership] = $this->member();
        $this->grant($membership, 'audit.view');

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/administration/audit')->assertOk();

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/administration/settings')->assertForbidden();
    }

    public function test_lifecycle_surface_exposes_active_tenant_locations_for_domain_forms(): void
    {
        [$tenant, $user, $membership] = $this->member();
        $this->grant($membership, 'assets.view');
        $location = AssetLocation::query()->create(['tenant_id' => $tenant->id, 'code' => 'DEST', 'name' => 'Gudang Desa', 'location_type' => 'warehouse', 'status' => 'active']);

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/lifecycle/mutations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locations.0.id', $location->id)->where('locations.0.name', 'Gudang Desa'));
    }

    public function test_inertia_shares_flash_feedback(): void
    {
        [$tenant, $user] = $this->member();

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid, 'success' => 'Data berhasil disimpan.'])
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Data berhasil disimpan.'));
    }

    public function test_lifecycle_uses_domain_permission_instead_of_generic_asset_update(): void
    {
        [$tenant, $user, $membership] = $this->member();
        $this->grant($membership, 'maintenance.manage');

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/lifecycle/maintenance', ['asset_id' => 999999, 'maintenance_type' => 'preventive'])
            ->assertNotFound();
    }

    public function test_transfer_rejects_mixed_tenant_asset_ids_without_partial_request(): void
    {
        [$tenant, $user, $membership] = $this->member();
        $this->grant($membership, 'transfers.request');
        $own = $this->asset($tenant, $user, 'Own');
        $foreignTenant = Tenant::factory()->active()->create();
        $foreign = $this->asset($foreignTenant, $user, 'Foreign');

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/lifecycle/transfers', ['asset_ids' => [$own->id, $foreign->id], 'transfer_type' => 'EXCHANGE'])
            ->assertNotFound();

        $this->assertDatabaseCount('asset_transfers', 0);
    }

    private function member(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return [$tenant, $user, $membership];
    }

    private function asset(Tenant $tenant, User $user, string $name): Asset
    {
        $scheme = ClassificationScheme::query()->create(['code' => uniqid('scheme-'), 'name' => 'Scheme', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $classification = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => uniqid('class-'), 'name' => 'General', 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->create(['code' => uniqid('unit-'), 'name' => 'Unit', 'status' => 'active']);

        return Asset::query()->create(['tenant_id' => $tenant->id, 'classification_id' => $classification->id, 'name' => $name, 'quantity' => 1, 'unit_id' => $unit->id, 'condition' => 'good', 'created_by' => $user->id, 'updated_by' => $user->id]);
    }

    private function grant(TenantMembership $membership, string $code): void
    {
        $permission = Permission::query()->create(['code' => $code, 'domain' => strtok($code, '.'), 'action' => str($code)->after('.')->toString()]);
        $role = Role::query()->create(['code' => 'surface-'.uniqid(), 'name' => 'Surface role', 'scope_type' => 'tenant', 'is_system' => false]);
        $role->permissions()->attach($permission);
        $membership->roles()->attach($role, ['assigned_at' => now()]);
    }
}
