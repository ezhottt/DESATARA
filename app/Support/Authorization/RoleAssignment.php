<?php

namespace App\Support\Authorization;

use App\Models\Role;
use App\Models\TenantMembership;
use App\Models\User;
use InvalidArgumentException;

final class RoleAssignment
{
    public function assign(TenantMembership $membership, Role $role, ?User $actor = null): void
    {
        if ($role->scope_type !== 'tenant') {
            throw new InvalidArgumentException('Only tenant roles may be assigned to tenant memberships.');
        }
        $membership->roles()->syncWithoutDetaching([$role->id => ['assigned_by' => $actor?->id, 'assigned_at' => now()]]);
    }
}
