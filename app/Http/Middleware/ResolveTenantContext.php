<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->session()->get('active_tenant_uuid');
        abort_unless(is_string($uuid) && $uuid !== '', 403, 'No active tenant.');
        $tenant = Tenant::query()->where('uuid', $uuid)->firstOrFail();
        $membership = TenantMembership::query()->where('tenant_id', $tenant->id)->where('user_id', $request->user()->id)->first();
        abort_unless($membership?->isCurrentlyActive(), 403, 'Active tenant membership required.');
        $this->context->set($tenant);
        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
