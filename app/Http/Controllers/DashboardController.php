<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\AssetReport;
use App\Models\InventoryDiscrepancy;
use App\Models\InventorySession;
use App\Support\Authorization\PermissionResolver;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(TenantContext $context, PermissionResolver $permissions, Request $request): Response
    {
        $tenantId = $context->id();
        $canViewAssets = $permissions->allows($request->user(), $context->tenant(), 'assets.view');

        return Inertia::render('Dashboard', [
            'tenant' => ['name' => $context->tenant()->name],
            'metrics' => $canViewAssets ? [
                'total_assets' => Asset::query()->where('tenant_id', $tenantId)->count(),
                'total_value' => Asset::query()->where('tenant_id', $tenantId)->sum('acquisition_value'),
                'active_assets' => Asset::query()->where('tenant_id', $tenantId)->where('lifecycle_status', 'active')->count(),
                'unverified_assets' => Asset::query()->where('tenant_id', $tenantId)->where('verification_status', 'unverified')->count(),
                'damaged_assets' => Asset::query()->where('tenant_id', $tenantId)->whereIn('condition', ['damaged', 'broken'])->count(),
                'open_discrepancies' => InventoryDiscrepancy::query()->where('tenant_id', $tenantId)->where('status', 'open')->count(),
                'pending_approvals' => ApprovalRequest::query()->where('tenant_id', $tenantId)->whereIn('status', ['pending', 'submitted', 'in_review'])->count(),
                'active_inventory_sessions' => InventorySession::query()->where('tenant_id', $tenantId)->whereIn('status', ['in_progress', 'started', 'review'])->count(),
                'pending_maintenance' => AssetMaintenance::query()->where('tenant_id', $tenantId)->whereIn('status', ['planned', 'pending', 'in_progress'])->count(),
                'open_reports' => AssetReport::query()->where('tenant_id', $tenantId)->whereIn('status', ['draft', 'review'])->count(),
            ] : [],
            'canViewAssets' => $canViewAssets,
        ]);
    }
}
