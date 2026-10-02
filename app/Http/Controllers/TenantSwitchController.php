<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSwitchController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($tenant->isOperational(), 403, 'Tenant is not available.');
        $membership = TenantMembership::query()->where('tenant_id', $tenant->id)->where('user_id', $request->user()->id)->first();
        abort_unless($membership?->isCurrentlyActive(), 403, 'Active tenant membership required.');
        $request->session()->put('active_tenant_uuid', $tenant->uuid);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
