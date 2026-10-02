<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\ActiveTenantMembership;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(private TenantContext $context, private ActiveTenantMembership $memberships) {}

    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->session()->get('active_tenant_uuid');
        abort_unless(is_string($uuid) && $uuid !== '', 403, 'No active tenant.');
        $tenant = Tenant::query()->where('uuid', $uuid)->firstOrFail();
        abort_unless($this->memberships->exists($request->user()->id, $tenant->id), 403, 'Active tenant membership required.');
        $this->context->set($tenant);
        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
