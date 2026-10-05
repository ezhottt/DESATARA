<?php

namespace App\Support\Authority;

use App\Models\Tenant;
use App\Models\TenantOfficial;
use App\Models\User;
use App\Support\Tenancy\ActiveTenantMembership;

final class AuthorityResolver
{
    public function __construct(private ActiveTenantMembership $memberships) {}

    public function resolve(User $user, Tenant $tenant, string $code, string $scope): ?TenantOfficial
    {
        $membership = $this->memberships->query($user->id, $tenant->id)->first();
        if (! $membership) {
            return null;
        }

        return TenantOfficial::query()->where('tenant_id', $tenant->id)->where('membership_id', $membership->id)->where('authority_code', $code)->where('status', 'active')->where('valid_from', '<=', today())->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', today()))->whereJsonContains('authority_scope', $scope)->orderByDesc('valid_from')->first();
    }

    public function allows(User $user, Tenant $tenant, string $code, string $scope): bool
    {
        return $this->resolve($user, $tenant, $code, $scope) !== null;
    }
}
