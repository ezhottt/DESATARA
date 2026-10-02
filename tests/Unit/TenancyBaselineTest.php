<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Support\Tenancy\TenantAwareJob;
use App\Support\Tenancy\TenantCacheKey;
use App\Support\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TenancyBaselineTest extends TestCase
{
    public function test_cache_key_is_tenant_namespaced(): void
    {
        $this->assertSame('desatara:42:assets:list:1', TenantCacheKey::make(42, 'assets', 'list'));
    }

    public function test_cache_key_rejects_ambiguous_segments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TenantCacheKey::make(1, 'assets:other', 'list');
    }

    public function test_context_can_be_cleared_after_work(): void
    {
        $context = new TenantContext;
        $this->assertFalse($context->hasTenant());
        $context->clear();
        $this->assertFalse($context->hasTenant());
    }

    public function test_tenant_aware_job_requires_explicit_tenant_id(): void
    {
        $job = new class(17) extends TenantAwareJob
        {
            protected function handleForTenant(Tenant $tenant, TenantContext $context): void {}
        };
        $this->assertSame(17, $job->tenantId);
    }
}
