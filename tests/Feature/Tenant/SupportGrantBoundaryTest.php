<?php

namespace Tests\Feature\Tenant;

use App\Models\PlatformSupportGrant;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\SupportGrantAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportGrantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_grant_allows_only_declared_scope_for_target_tenant(): void
    {
        [$grant, $tenant, $user] = $this->grant(['read']);

        $access = app(SupportGrantAccess::class);

        $this->assertTrue($access->allows($user, $tenant, 'read'));
        $this->assertFalse($access->allows($user, $tenant, 'write'));
        $this->assertTrue($grant->isValid());
    }

    public function test_expired_revoked_wrong_tenant_and_wrong_user_are_denied(): void
    {
        [$grant, $tenant, $user] = $this->grant(['read']);
        $access = app(SupportGrantAccess::class);

        $grant->update(['valid_until' => now()->subSecond()]);
        $this->assertFalse($access->allows($user, $tenant, 'read'));

        $grant->update(['valid_until' => now()->addHour(), 'revoked_at' => now()]);
        $this->assertFalse($access->allows($user, $tenant, 'read'));

        $this->assertFalse($access->allows($user, Tenant::factory()->active()->create(), 'read'));
        $this->assertFalse($access->allows(User::factory()->create(), $tenant, 'read'));
    }

    private function grant(array $scope): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();

        $grant = PlatformSupportGrant::query()->create([
            'tenant_id' => $tenant->id,
            'platform_user_id' => $user->id,
            'scope' => $scope,
            'reason' => 'Diagnostic support',
            'granted_by_user_id' => User::factory()->create()->id,
            'valid_from' => now()->subMinute(),
            'valid_until' => now()->addHour(),
            'audit_correlation_id' => fake()->uuid(),
        ]);

        return [$grant, $tenant, $user];
    }
}
