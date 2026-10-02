<?php

namespace App\Support\Tenancy;

use App\Models\TenantMembership;
use Illuminate\Database\Eloquent\Builder;

final class ActiveTenantMembership
{
    /** @return Builder<TenantMembership> */
    public function query(int $userId, int $tenantId): Builder
    {
        $now = now();

        return TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where(fn (Builder $query) => $query->whereNull('valid_from')->orWhere('valid_from', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', $now))
            ->orderByDesc('valid_from')
            ->orderByDesc('id');
    }

    public function exists(int $userId, int $tenantId): bool
    {
        return $this->query($userId, $tenantId)->exists();
    }
}
