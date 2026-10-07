<?php

use App\Http\Controllers\AssetSearchController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\ProductSurfaceController;
use App\Http\Controllers\TenantContextController;
use App\Http\Controllers\TenantSwitchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/', function (Request $request) {
    if ($request->session()->has('active_tenant_uuid')) {
        return redirect()->route('dashboard');
    }

    $memberships = $request->user()->tenantMemberships()
        ->with('tenant:id,uuid,name,status')
        ->where('status', 'active')
        ->get()
        ->filter(fn ($membership) => $membership->isCurrentlyActive() && $membership->tenant->isOperational())
        ->values();

    if ($memberships->count() === 1) {
        $request->session()->put('active_tenant_uuid', $memberships->first()->tenant->uuid);

        return redirect()->route('dashboard');
    }

    return Inertia::render('Foundation', ['product' => 'DESATARA']);
})->middleware('auth')->name('home');
Route::get('/qr/{token}', [EvidenceController::class, 'publicQr'])->middleware('throttle:60,1')->name('qr.public');
Route::get('/verifikasi-aset/{uuid}', [EvidenceController::class, 'publicAsset'])->middleware('throttle:60,1')->name('assets.verify');

Route::middleware('auth')->group(function () {
    Route::post('/tenant/switch/{tenant:uuid}', TenantSwitchController::class)->name('tenant.switch');
    Route::middleware('tenant.context')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/search', AssetSearchController::class)->middleware(['permission:assets.view', 'throttle:60,1'])->name('search');
        Route::get('/tenant/context', TenantContextController::class)->name('tenant.context');
        Route::get('/documents/{uuid}/download', [EvidenceController::class, 'download'])->middleware('permission:documents.view')->name('documents.download');
        Route::get('/administration/rbac', [GovernanceController::class, 'rbac'])->middleware('permission:tenant.settings.update')->name('governance.rbac');
        Route::get('/administration/authorities', [GovernanceController::class, 'authorities'])->middleware('permission:tenant.settings.update')->name('governance.authorities');
        Route::get('/administration/regulations', [GovernanceController::class, 'regulations'])->middleware('permission:audit.view')->name('governance.regulations');
        Route::prefix('master-data')->middleware('permission:tenant.settings.update')->group(function () {
            Route::get('/{type}', [ProductSurfaceController::class, 'masterData'])->whereIn('type', ['classifications', 'locations', 'organizational-units', 'funding-sources', 'responsible-parties'])->name('master-data.index');
            Route::post('/{type}', [ProductSurfaceController::class, 'storeMasterData'])->whereIn('type', ['locations', 'organizational-units', 'funding-sources', 'responsible-parties'])->middleware('tenant.operational')->name('master-data.store');
        });
        Route::get('/assets', [ProductSurfaceController::class, 'assets'])->middleware('permission:assets.view')->name('assets.index');
        Route::get('/assets/create', [ProductSurfaceController::class, 'createAsset'])->middleware('permission:assets.create')->name('assets.create');
        Route::post('/assets/labels/prepare', [ProductSurfaceController::class, 'prepareAssetLabels'])->middleware(['permission:documents.manage', 'tenant.operational'])->name('assets.labels.prepare');

        Route::post('/assets', [ProductSurfaceController::class, 'storeAsset'])->middleware(['permission:assets.create', 'tenant.operational'])->name('assets.store');
        Route::get('/assets/{uuid}/edit', [ProductSurfaceController::class, 'editAsset'])->middleware('permission:assets.update')->name('assets.edit');
        Route::get('/assets/{uuid}', [ProductSurfaceController::class, 'showAsset'])->middleware('permission:assets.view')->name('assets.show');
        Route::patch('/assets/{uuid}', [ProductSurfaceController::class, 'updateAsset'])->middleware(['permission:assets.update', 'tenant.operational'])->name('assets.update');
        Route::post('/assets/{uuid}/documents', [ProductSurfaceController::class, 'document'])->middleware(['permission:documents.manage', 'tenant.operational', 'throttle:10,1'])->name('assets.documents.store');
        Route::post('/assets/{uuid}/qr', [ProductSurfaceController::class, 'qr'])->middleware(['permission:documents.manage', 'tenant.operational'])->name('assets.qr.issue');
        Route::get('/inventory', [ProductSurfaceController::class, 'inventory'])->middleware('permission:inventory.execute')->name('inventory.index');
        Route::post('/inventory', [ProductSurfaceController::class, 'createInventory'])->middleware(['permission:inventory.execute', 'tenant.operational'])->name('inventory.store');
        Route::post('/inventory/{uuid}/freeze', [ProductSurfaceController::class, 'prepareInventory'])->middleware(['permission:inventory.execute', 'tenant.operational'])->name('inventory.freeze');
        Route::post('/inventory/items/{id}/observe', [ProductSurfaceController::class, 'observeInventory'])->middleware(['permission:inventory.execute', 'tenant.operational'])->name('inventory.observe');
        Route::post('/inventory/discrepancies/{id}/reconcile', [ProductSurfaceController::class, 'reconcileInventory'])->middleware(['permission:inventory.execute', 'tenant.operational'])->name('inventory.reconcile');
        Route::post('/inventory/{uuid}/finalize', [ProductSurfaceController::class, 'finalizeInventory'])->middleware(['permission:inventory.execute', 'tenant.operational'])->name('inventory.finalize');
        Route::get('/lifecycle/{type}', [ProductSurfaceController::class, 'lifecycle'])->middleware('permission:assets.view')->whereIn('type', ['maintenance', 'mutations', 'utilization', 'safeguarding', 'valuation', 'transfers', 'disposals', 'usage'])->name('lifecycle.index');
        Route::post('/lifecycle/{type}', [ProductSurfaceController::class, 'lifecycleAction'])->middleware('tenant.operational')->whereIn('type', ['maintenance', 'mutations', 'utilization', 'safeguarding', 'valuation', 'transfers', 'disposals', 'usage'])->name('lifecycle.action');
        Route::post('/lifecycle/{type}/{id}/execute', [ProductSurfaceController::class, 'lifecycleExecute'])->middleware('tenant.operational')->whereIn('type', ['maintenance', 'transfers', 'disposals', 'usage'])->name('lifecycle.execute');
        Route::get('/approvals', [ProductSurfaceController::class, 'approvals'])->middleware('permission:approvals.view')->name('approvals.index');
        Route::get('/approvals/{id}', [ProductSurfaceController::class, 'approvalDetail'])->middleware('permission:approvals.view')->name('approvals.show');
        Route::post('/approvals/{id}/action', [ProductSurfaceController::class, 'approvalAction'])->middleware(['permission:approvals.action', 'tenant.operational'])->name('approvals.action');
        Route::get('/reports', [ProductSurfaceController::class, 'reports'])->middleware('permission:reports.view')->name('reports.index');
        Route::post('/reports/periods', [ProductSurfaceController::class, 'createPeriod'])->middleware(['permission:reports.view', 'tenant.operational'])->name('reports.period');
        Route::post('/reports', [ProductSurfaceController::class, 'createReport'])->middleware(['permission:reports.view', 'tenant.operational'])->name('reports.store');
        Route::post('/reports/{id}/{action}', [ProductSurfaceController::class, 'reportAction'])->middleware(['permission:reports.export', 'tenant.operational'])->whereIn('action', ['review', 'finalize', 'revise'])->name('reports.action');
        Route::get('/reports/{id}/export', [ProductSurfaceController::class, 'reportExport'])->middleware('permission:reports.export')->name('reports.export');
        Route::get('/imports', [ProductSurfaceController::class, 'imports'])->middleware('permission:imports.view')->name('imports.index');
        Route::get('/imports/template', [ProductSurfaceController::class, 'importTemplate'])->middleware('permission:imports.view')->name('imports.template');
        Route::get('/imports/{uuid}', [ProductSurfaceController::class, 'importShow'])->middleware('permission:imports.view')->name('imports.show');
        Route::post('/imports/assets/preview', [ProductSurfaceController::class, 'importFilePreview'])->middleware(['permission:imports.create', 'tenant.operational', 'throttle:5,1'])->name('imports.preview');
        Route::post('/imports/{uuid}/commit', [ProductSurfaceController::class, 'importFileCommit'])->middleware(['permission:imports.commit', 'tenant.operational', 'throttle:5,1'])->name('imports.commit');
        Route::get('/interoperability/{type?}', [ProductSurfaceController::class, 'interoperability'])->whereIn('type', ['imports', 'exports'])->name('interoperability.index');
        Route::post('/interoperability/import/preview', [ProductSurfaceController::class, 'importPreview'])->middleware(['permission:assets.create', 'tenant.operational', 'throttle:5,1'])->name('interoperability.import.preview');
        Route::post('/interoperability/import/{id}/commit', [ProductSurfaceController::class, 'importCommit'])->middleware(['permission:assets.create', 'tenant.operational', 'throttle:5,1'])->name('interoperability.import.commit');
        Route::post('/interoperability/export', [ProductSurfaceController::class, 'export'])->middleware(['permission:reports.export', 'tenant.operational', 'throttle:5,1'])->name('interoperability.export');
        Route::get('/administration/{type}', [ProductSurfaceController::class, 'administration'])->whereIn('type', ['members', 'audit', 'settings'])->name('administration.index');
        Route::post('/administration/memberships', [ProductSurfaceController::class, 'addMembership'])->middleware(['permission:users.manage', 'tenant.operational'])->name('administration.membership');
        Route::put('/administration/settings', [ProductSurfaceController::class, 'updateSettings'])->middleware(['permission:tenant.settings.update', 'tenant.operational'])->name('administration.settings');
    });
});
