<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Authorization\PermissionResolver;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_matrix_is_seeded_deterministically(): void
    {
        $this->seed(RbacSeeder::class);
        $this->assertSame(8, Role::query()->count());
        $this->assertGreaterThanOrEqual(15, Permission::query()->count());
        $this->assertSame(1, Role::query()->where('code', 'tenant_admin')->where('scope_type', 'tenant')->count());
    }

    public function test_permission_is_resolved_through_active_membership_role(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();
        $role = Role::query()->create(['code' => 'operator', 'name' => 'Operator', 'scope_type' => 'tenant', 'is_system' => false]);
        $permission = Permission::query()->create(['code' => 'assets.view', 'domain' => 'assets', 'action' => 'view']);
        $role->permissions()->attach($permission);
        $membership->roles()->attach($role, ['assigned_at' => now()]);
        $this->assertTrue(app(PermissionResolver::class)->allows($user, $tenant, 'assets.view'));
        $this->assertFalse(app(PermissionResolver::class)->allows($user, $tenant, 'assets.update'));
    }

    public function test_role_on_other_tenant_membership_does_not_grant_access(): void
    {
        $a = Tenant::factory()->active()->create();
        $b = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $ma = TenantMembership::factory()->active()->for($user)->for($a)->create();
        TenantMembership::factory()->active()->for($user)->for($b)->create();
        $role = Role::query()->create(['code' => 'viewer', 'name' => 'Viewer', 'scope_type' => 'tenant', 'is_system' => false]);
        $permission = Permission::query()->create(['code' => 'assets.view', 'domain' => 'assets', 'action' => 'view']);
        $role->permissions()->attach($permission);
        $ma->roles()->attach($role, ['assigned_at' => now()]);
        $this->assertTrue(app(PermissionResolver::class)->allows($user, $a, 'assets.view'));
        $this->assertFalse(app(PermissionResolver::class)->allows($user, $b, 'assets.view'));
    }

    public function test_platform_role_does_not_create_implicit_tenant_permission(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $this->assertFalse(app(PermissionResolver::class)->allows($user,$tenant,'tenant.settings.update'));
    }
}
