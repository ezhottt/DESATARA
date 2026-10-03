<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use RuntimeException;

abstract class TenantAwareJob
{
    public function __construct(public readonly int $tenantId) {}

    final public function handle(TenantContext $context): void
    {
        $tenant = Tenant::query()->find($this->tenantId) ?? throw new RuntimeException('Tenant no longer exists.');
        if (! $tenant->isOperational()) {
            throw new RuntimeException('Tenant is not operational.');
        }

        try {
            $context->set($tenant);
            $this->handleForTenant($tenant, $context);
        } finally {
            $context->clear();
        }
    }

    abstract protected function handleForTenant(Tenant $tenant, TenantContext $context): void;
}
