<?php

namespace App\Http\Middleware;

use App\Support\Authorization\PermissionResolver;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        $context = app(TenantContext::class);
        $tenant = $context->hasTenant() ? $context->tenant() : null;
        $tenants = $user ? $user->tenantMemberships()->with('tenant:id,uuid,name,status')->where('status', 'active')->get()->filter(fn ($membership) => $membership->isCurrentlyActive() && $membership->tenant->isOperational())->map(fn ($membership) => $membership->tenant->only(['uuid', 'name']))->values() : collect();
        $permissions = [];
        if ($user && $tenant) {
            $resolver = app(PermissionResolver::class);
            foreach (['assets.view', 'assets.create', 'assets.update', 'documents.view', 'documents.manage', 'mutations.request', 'maintenance.manage', 'inventory.execute', 'transfers.request', 'disposals.request', 'approvals.view', 'approvals.action', 'reports.view', 'reports.export', 'audit.view', 'users.manage', 'tenant.settings.update'] as $permission) {
                $permissions[$permission] = $resolver->allows($user, $tenant, $permission);
            }
        }

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
            'tenantContext' => $tenant ? ['uuid' => $tenant->uuid, 'name' => $tenant->name, 'status' => $tenant->status] : null,
            'tenantOptions' => $tenants,
            'permissions' => $permissions,
        ];
    }
}
