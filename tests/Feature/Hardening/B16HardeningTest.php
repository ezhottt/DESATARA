<?php

namespace Tests\Feature\Hardening;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class B16HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_send_security_and_cache_headers(): void
    {
        [$tenant, $user] = $this->context();

        foreach (['/dashboard', '/search'] as $path) {
            $response = $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])->get($path);

            $response->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Cache-Control', 'no-store, private');
        }
    }

    public function test_static_pages_keep_accessibility_hardening_markers(): void
    {
        $dashboard = file_get_contents(resource_path('js/Pages/Dashboard.vue'));
        $search = file_get_contents(resource_path('js/Pages/Search.vue'));

        $this->assertStringContainsString('<main', $dashboard);
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('for="asset-search"', $search);
    }

    private function context(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();
        $permission = Permission::query()->create(['code' => 'assets.view', 'domain' => 'assets', 'action' => 'view']);
        $role = Role::query()->create(['code' => 'b16-viewer', 'name' => 'B16 Viewer', 'scope_type' => 'tenant', 'is_system' => false]);
        $role->permissions()->attach($permission);
        $membership->roles()->attach($role, ['assigned_at' => now()]);

        return [$tenant, $user];
    }
}
