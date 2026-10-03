<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use LogicException;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function tenant(): Tenant
    {
        return $this->tenant ?? throw new LogicException('Tenant context has not been established.');
    }

    public function id(): int
    {
        return $this->tenant()->getKey();
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}
