<?php

namespace Tests\Feature\Governance;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernanceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_governance_route_is_denied_without_permission(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        TenantMembership::factory()->active()->for($user)->for($tenant)->create();
        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])->get('/administration/rbac')->assertForbidden();
    }

    public function test_tenant_admin_can_open_rbac_surface(): void
    {
        $this->seed(RbacSeeder::class);
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();
        $role = Role::query()->where('code', 'tenant_admin')->firstOrFail();
        $membership->roles()->attach($role, ['assigned_at' => now()]);
        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])->get('/administration/rbac')->assertOk()->assertInertia(fn ($page) => $page->component('Governance/Rbac'));
    }
}
