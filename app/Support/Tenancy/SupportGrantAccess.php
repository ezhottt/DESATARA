<?php

namespace App\Support\Tenancy;

use App\Models\PlatformSupportGrant;
use App\Models\Tenant;
use App\Models\User;

final class SupportGrantAccess
{
    public function allows(User $user, Tenant $tenant, string $scope): bool
    {
        $now = now();

        return PlatformSupportGrant::query()
            ->where('tenant_id', $tenant->id)
            ->where('platform_user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('valid_from', '<=', $now)
            ->where('valid_until', '>=', $now)
            ->whereJsonContains('scope', $scope)
            ->exists();
    }
}
