<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Authorization\PermissionResolver;
use App\Support\Tenancy\ActiveTenantMembership;
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
        if (! $tenant && $user) {
            $uuid = $request->session()->get('active_tenant_uuid');
            if (is_string($uuid) && $uuid !== '') {
                $candidate = Tenant::query()->where('uuid', $uuid)->first();
                if ($candidate && $candidate->isOperational() && app(ActiveTenantMembership::class)->exists($user->id, $candidate->id)) {
                    $tenant = $candidate;
                }
            }
        }
        $tenants = $user ? $user->tenantMemberships()->with('tenant:id,uuid,name,status')->where('status', 'active')->get()->filter(fn ($membership) => $membership->isCurrentlyActive() && $membership->tenant->isOperational())->map(fn ($membership) => $membership->tenant->only(['uuid', 'name']))->values() : collect();
        $permissions = [];
        if ($user && $tenant) {
            $resolver = app(PermissionResolver::class);
            foreach (['assets.view', 'assets.create', 'assets.update', 'documents.view', 'documents.manage', 'mutations.request', 'maintenance.manage', 'inventory.execute', 'transfers.request', 'disposals.request', 'approvals.view', 'approvals.action', 'reports.view', 'reports.export', 'audit.view', 'users.manage', 'tenant.settings.update', 'imports.view', 'imports.create', 'imports.commit'] as $permission) {
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
            'flash' => ['success' => fn () => $request->session()->get('success'), 'error' => fn () => $request->session()->get('error')],
        ];
    }
}
