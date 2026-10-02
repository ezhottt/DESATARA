<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        [$user, $tenant] = $this->activeMember(Tenant::factory()->suspended()->create());

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/tenant/mutation-probe')
            ->assertStatus(423);
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
