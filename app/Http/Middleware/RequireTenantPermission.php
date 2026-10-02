<?php

namespace App\Http\Middleware;

use App\Support\Authorization\PermissionResolver;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTenantPermission
{
    public function __construct(private PermissionResolver $permissions, private TenantContext $context) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($this->permissions->allows($request->user(), $this->context->tenant(), $permission), 403);

        return $next($request);
    }
}
