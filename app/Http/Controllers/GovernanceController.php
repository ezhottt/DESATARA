<?php

namespace App\Http\Controllers;

use App\Models\BusinessRule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TenantOfficial;
use App\Support\Tenancy\TenantContext;
use Inertia\Inertia;
use Inertia\Response;

class GovernanceController extends Controller
{
    public function rbac(): Response
    {
        return Inertia::render('Governance/Rbac', ['roles' => Role::query()->withCount('permissions')->orderBy('name')->get(['id', 'code', 'name', 'scope_type']), 'permissions' => Permission::query()->orderBy('code')->get(['id', 'code', 'domain', 'action'])]);
    }

    public function authorities(TenantContext $context): Response
    {
        return Inertia::render('Governance/Authorities', ['officials' => TenantOfficial::query()->where('tenant_id', $context->id())->orderBy('position_name')->get(['id', 'official_type', 'position_name', 'authority_code', 'status', 'valid_from', 'valid_until'])]);
    }

    public function regulations(): Response
    {
        return Inertia::render('Governance/Regulations', ['rules' => BusinessRule::query()->withCount(['provisions', 'bindings'])->orderBy('code')->get(['id', 'code', 'name', 'status', 'implementation_status', 'is_mandatory', 'jurisdiction_level'])]);
    }
}
