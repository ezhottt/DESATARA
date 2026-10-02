<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Support\Tenancy\TenantAwareJob;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TenantAwareJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_establishes_correct_context_and_clears_it_after_success(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $context = app(TenantContext::class);
        $seen = null;

        $job = new class($tenant->id, $seen) extends TenantAwareJob
        {
            public function __construct(int $tenantId, public mixed &$seen)
            {
                parent::__construct($tenantId);
            }

            protected function handleForTenant(Tenant $tenant, TenantContext $context): void
            {
                $this->seen = $context->id();
            }
        };

        $job->handle($context);

        $this->assertSame($tenant->id, $job->seen);
        $this->assertFalse($context->hasTenant());
    }

    public function test_job_clears_context_after_domain_exception(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $context = app(TenantContext::class);

        $job = new class($tenant->id) extends TenantAwareJob
        {
            protected function handleForTenant(Tenant $tenant, TenantContext $context): void
            {
                throw new RuntimeException('boom');
            }
        };

        try {
            $job->handle($context);
            $this->fail('Expected exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertFalse($context->hasTenant());
    }

    public function test_suspended_or_archived_tenant_job_is_denied_before_domain_execution(): void
    {
        foreach (['suspended', 'archived'] as $status) {
            $tenant = Tenant::factory()->create(['status' => $status]);
            $context = app(TenantContext::class);
            $executed = false;

            $job = new class($tenant->id, $executed) extends TenantAwareJob
            {
                public function __construct(int $tenantId, public bool &$executed)
                {
                    parent::__construct($tenantId);
                }

                protected function handleForTenant(Tenant $tenant, TenantContext $context): void
                {
                    $this->executed = true;
                }
            };

            try {
                $job->handle($context);
                $this->fail('Expected tenant-state exception was not thrown.');
            } catch (RuntimeException) {
                $this->assertFalse($job->executed);
                $this->assertFalse($context->hasTenant());
            }
        }
    }

    public function test_deleted_tenant_job_is_denied_and_context_stays_clear(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $tenantId = $tenant->id;
        $tenant->delete();
        $context = app(TenantContext::class);

        $job = new class($tenantId) extends TenantAwareJob
        {
            protected function handleForTenant(Tenant $tenant, TenantContext $context): void {}
        };

        try {
            $job->handle($context);
            $this->fail('Expected missing tenant exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Tenant no longer exists.', $exception->getMessage());
        }

        $this->assertFalse($context->hasTenant());
    }

    public function test_sequential_jobs_never_reuse_previous_tenant_context(): void
    {
        $first = Tenant::factory()->active()->create();
        $second = Tenant::factory()->active()->create();
        $context = app(TenantContext::class);
        $seen = [];

        foreach ([$first, $second] as $tenant) {
            $job = new class($tenant->id, $seen) extends TenantAwareJob
            {
                public function __construct(int $tenantId, public array &$seen)
                {
                    parent::__construct($tenantId);
                }

                protected function handleForTenant(Tenant $tenant, TenantContext $context): void
                {
                    $this->seen[] = $context->id();
                }
            };

            $job->handle($context);
            $this->assertFalse($context->hasTenant());
        }

        $this->assertSame([$first->id, $second->id], $seen);
    }
}
