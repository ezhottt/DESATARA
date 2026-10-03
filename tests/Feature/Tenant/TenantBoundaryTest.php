<?php

namespace Tests\Feature\Tenant;

use App\Http\Middleware\EnsureTenantOperational;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TenantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_membership_resolves_tenant_context(): void
    {
        [$user, $tenant] = $this->activeMember();

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/tenant/context')
            ->assertOk()
            ->assertJsonPath('tenant.uuid', $tenant->uuid);
    }

    public function test_member_cannot_resolve_another_tenant(): void
    {
        [$user] = $this->activeMember();
        $other = Tenant::factory()->active()->create();

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $other->uuid])
            ->get('/tenant/context')
            ->assertForbidden();
    }

    public function test_client_tenant_id_cannot_override_server_context(): void
    {
        [$user, $tenant] = $this->activeMember();
        $other = Tenant::factory()->active()->create();

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/tenant/context?tenant_id='.$other->id)
            ->assertOk()
            ->assertJsonPath('tenant.uuid', $tenant->uuid);
    }

    public function test_authenticated_user_can_switch_only_to_active_membership(): void
    {
        $user = User::factory()->create();
        $first = Tenant::factory()->active()->create();
        $second = Tenant::factory()->active()->create();
        TenantMembership::factory()->active()->for($user)->for($first)->create();
        TenantMembership::factory()->active()->for($user)->for($second)->create();

        $this->actingAs($user)
            ->post('/tenant/switch/'.$second->uuid)
            ->assertRedirect('/');

        $this->assertSame($second->uuid, session('active_tenant_uuid'));
    }

    public function test_switch_to_tenant_without_membership_is_denied(): void
    {
        [$user, $tenant] = $this->activeMember();
        $other = Tenant::factory()->active()->create();

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/tenant/switch/'.$other->uuid)
            ->assertForbidden();

        $this->assertSame($tenant->uuid, session('active_tenant_uuid'));
    }

    public function test_suspended_tenant_blocks_ordinary_mutation(): void
    {
        $tenant = Tenant::factory()->suspended()->create();
        $context = app(TenantContext::class);
        $context->set($tenant);
        $middleware = app(EnsureTenantOperational::class);

        try {
            $middleware->handle(request(), fn () => response()->noContent());
            $this->fail('Expected suspended tenant mutation to be blocked.');
        } catch (HttpException $exception) {
            $this->assertSame(423, $exception->getStatusCode());
        } finally {
            $context->clear();
        }
    }

    public function test_expired_membership_cannot_resolve_tenant_context(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->active()->create();

        TenantMembership::factory()->for($user)->for($tenant)->create([
            'status' => 'active',
            'valid_from' => now()->subDays(2),
            'valid_until' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/tenant/context')
            ->assertForbidden();
    }

    public function test_revoked_membership_cannot_resolve_tenant_context(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->active()->create();

        TenantMembership::factory()->for($user)->for($tenant)->create([
            'status' => 'revoked',
            'valid_from' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/tenant/context')
            ->assertForbidden();
    }

    public function test_future_membership_cannot_resolve_tenant_context(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->active()->create();

        TenantMembership::factory()->for($user)->for($tenant)->create([
            'status' => 'active',
            'valid_from' => now()->addHour(),
            'valid_until' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/tenant/context')
            ->assertForbidden();
    }

    public function test_switch_rejects_non_operational_tenant_even_with_active_membership(): void
    {
        foreach (['pending', 'suspended', 'archived'] as $status) {
            $user = User::factory()->create();
            $tenant = Tenant::factory()->create(['status' => $status]);
            TenantMembership::factory()->active()->for($user)->for($tenant)->create();

            $this->actingAs($user)
                ->post('/tenant/switch/'.$tenant->uuid)
                ->assertForbidden();
        }
    }

    public function test_historical_membership_does_not_shadow_current_active_membership(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->active()->create();

        TenantMembership::factory()->for($user)->for($tenant)->create([
            'status' => 'revoked',
            'valid_from' => now()->subYear(),
            'valid_until' => now()->subMonths(6),
        ]);
        TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/tenant/context')
            ->assertOk()
            ->assertJsonPath('tenant.uuid', $tenant->uuid);
    }

    public function test_switch_discards_stale_tenant_context_on_next_request(): void
    {
        $user = User::factory()->create();
        $first = Tenant::factory()->active()->create();
        $second = Tenant::factory()->active()->create();
        TenantMembership::factory()->active()->for($user)->for($first)->create();
        TenantMembership::factory()->active()->for($user)->for($second)->create();

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $first->uuid])
            ->get('/tenant/context')
            ->assertJsonPath('tenant.uuid', $first->uuid);

        $this->post('/tenant/switch/'.$second->uuid)->assertRedirect('/');

        $this->get('/tenant/context')
            ->assertOk()
            ->assertJsonPath('tenant.uuid', $second->uuid);
    }

    public function test_production_routes_do_not_expose_mutation_probe(): void
    {
        $this->assertFalse(Route::has('tenant.mutation-probe'));
    }

    public function test_support_grant_does_not_create_implicit_membership(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->active()->create();

        $tenant->supportGrants()->create([
            'platform_user_id' => $user->id,
            'scope' => ['read'],
            'reason' => 'Support diagnostic',
            'granted_by_user_id' => User::factory()->create()->id,
            'valid_from' => now()->subMinute(),
            'valid_until' => now()->addHour(),
            'audit_correlation_id' => fake()->uuid(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->get('/tenant/context')
            ->assertForbidden();

        $this->assertDatabaseMissing('tenant_memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
    }

    private function activeMember(?Tenant $tenant = null): array
    {
        $user = User::factory()->create();
        $tenant ??= Tenant::factory()->active()->create();

        TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return [$user, $tenant];
    }
}
