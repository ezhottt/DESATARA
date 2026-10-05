<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_root_renders_empty_foundation_without_available_tenant(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Foundation')
                ->where('product', 'DESATARA')
                ->has('tenantOptions', 0)
            );
    }

    public function test_authenticated_root_auto_selects_single_active_tenant(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->active()->create();
        TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        $this->actingAs($user)->get('/')
            ->assertRedirect('/dashboard');

        $this->assertSame($tenant->uuid, session('active_tenant_uuid'));
    }

    public function test_authenticated_root_lists_multiple_active_tenants_for_selection(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $first = Tenant::factory()->active()->create(['name' => 'Desa Pertama']);
        $second = Tenant::factory()->active()->create(['name' => 'Desa Kedua']);
        TenantMembership::factory()->active()->for($user)->for($first)->create();
        TenantMembership::factory()->active()->for($user)->for($second)->create();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Foundation')
                ->has('tenantOptions', 2)
                ->where('tenantOptions.0.uuid', $first->uuid)
                ->where('tenantOptions.1.uuid', $second->uuid)
            );
    }
}
