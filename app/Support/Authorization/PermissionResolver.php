<?php

namespace App\Support\Authorization;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\ActiveTenantMembership;

final class PermissionResolver
{
    public function __construct(private ActiveTenantMembership $memberships) {}

    public function allows(User $user, Tenant $tenant, string $permission): bool
    {
        $membership = $this->memberships->query($user->id, $tenant->id)->first();
        if (! $membership) {
            return false;
        }

        return $membership->roles()->where('scope_type', 'tenant')->whereHas('permissions', fn ($q) => $q->where('code', $permission))->exists();
    }
}
