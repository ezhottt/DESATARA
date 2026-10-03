<?php

namespace Tests\Feature\Authority;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\TenantOfficial;
use App\Models\User;
use App\Support\Authority\AuthorityResolver;
use App\Support\Authority\AuthoritySnapshotter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_authority_resolves_and_snapshot_is_historical(): void
    {
        [$user,$tenant,$membership] = $this->member();
        $official = TenantOfficial::query()->create(['tenant_id' => $tenant->id, 'membership_id' => $membership->id, 'official_type' => 'kepala_desa', 'position_name' => 'Kepala Desa', 'authority_code' => 'assets.regulated.approve', 'authority_scope' => ['assets'], 'valid_from' => today()->subDay(), 'status' => 'active']);
        $resolver = app(AuthorityResolver::class);
        $this->assertTrue($resolver->allows($user, $tenant, 'assets.regulated.approve', 'assets'));
        $snapshot = app(AuthoritySnapshotter::class)->capture($user, $tenant, 'assets.regulated.approve', 'assets');
        $official->update(['status' => 'revoked']);
        $this->assertSame('Kepala Desa', $snapshot->position_name);
        $this->assertSame('assets.regulated.approve', $snapshot->authority_type);
    }

    public function test_expired_future_revoked_wrong_tenant_scope_and_missing_are_denied(): void
    {
        [$user,$tenant,$membership] = $this->member();
        $resolver = app(AuthorityResolver::class);
        foreach ([['expired', today()->subDays(2), today()->subDay()], ['active', today()->addDay(), null], ['revoked', today()->subDay(), null]] as [$status,$from,$until]) {
            $o = TenantOfficial::query()->create(['tenant_id' => $tenant->id, 'membership_id' => $membership->id, 'official_type' => 'official', 'position_name' => 'Official', 'authority_code' => 'approve', 'authority_scope' => ['assets'], 'valid_from' => $from, 'valid_until' => $until, 'status' => $status]);
            $this->assertFalse($resolver->allows($user, $tenant, 'approve', 'assets'));
            $o->delete();
        }
        TenantOfficial::query()->create(['tenant_id' => $tenant->id, 'membership_id' => $membership->id, 'official_type' => 'official', 'position_name' => 'Official', 'authority_code' => 'approve', 'authority_scope' => ['reports'], 'valid_from' => today()->subDay(), 'status' => 'active']);
        $this->assertFalse($resolver->allows($user, $tenant, 'approve', 'assets'));
        $this->assertFalse($resolver->allows($user, Tenant::factory()->active()->create(), 'approve', 'reports'));
        $this->assertFalse($resolver->allows(User::factory()->create(), $tenant, 'approve', 'reports'));
    }

    public function test_permission_does_not_substitute_for_authority(): void
    {
        [$user,$tenant] = $this->member();
        $this->assertFalse(app(AuthorityResolver::class)->allows($user, $tenant, 'assets.regulated.approve', 'assets'));
    }

    private function member(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $m = TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return [$user, $tenant, $m];
    }
}
