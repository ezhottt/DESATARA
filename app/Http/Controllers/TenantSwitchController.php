<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\Tenancy\ActiveTenantMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSwitchController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant, ActiveTenantMembership $memberships): RedirectResponse
    {
        abort_unless($tenant->isOperational(), 403, 'Tenant is not available.');
        abort_unless($memberships->exists($request->user()->id, $tenant->id), 403, 'Active tenant membership required.');
        $request->session()->put('active_tenant_uuid', $tenant->uuid);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
