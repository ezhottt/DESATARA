<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $codes = ['assets.view', 'assets.create', 'assets.update', 'documents.view', 'documents.manage', 'mutations.request', 'maintenance.manage', 'inventory.execute', 'transfers.request', 'disposals.request', 'approvals.view', 'approvals.action', 'reports.view', 'reports.export', 'audit.view', 'users.manage', 'tenant.settings.update'];
        foreach ($codes as $code) {
            [$domain,$action] = explode('.', $code, 2);
            Permission::query()->updateOrCreate(['code' => $code], ['domain' => $domain, 'action' => $action]);
        }
        $roles = [['platform_admin', 'Platform Admin', 'platform'], ['tenant_admin', 'Tenant Admin', 'tenant'], ['kepala_desa', 'Kepala Desa', 'tenant'], ['sekretaris_desa', 'Sekretaris Desa', 'tenant'], ['pengurus_aset', 'Pengurus Aset', 'tenant'], ['bpd_monitoring', 'BPD / Monitoring', 'tenant'], ['auditor', 'Auditor / Pemeriksa', 'tenant'], ['viewer', 'Viewer', 'tenant']];
        foreach ($roles as [$code,$name,$scope]) {
            Role::query()->updateOrCreate(['scope_type' => $scope, 'code' => $code], ['name' => $name, 'is_system' => true]);
        }
        $all = Permission::query()->pluck('id');
        Role::query()->where('code', 'tenant_admin')->where('scope_type', 'tenant')->firstOrFail()->permissions()->sync($all);
        $view = Permission::query()->whereIn('code', ['assets.view', 'documents.view', 'reports.view', 'audit.view'])->pluck('id');
        foreach (['kepala_desa', 'sekretaris_desa', 'bpd_monitoring', 'auditor', 'viewer'] as $code) {
            Role::query()->where('code', $code)->firstOrFail()->permissions()->sync($view);
        }
        $operator = Permission::query()->whereIn('code', ['assets.view', 'assets.create', 'assets.update', 'documents.view', 'documents.manage', 'mutations.request', 'maintenance.manage', 'inventory.execute', 'transfers.request', 'disposals.request', 'reports.view'])->pluck('id');
        Role::query()->where('code', 'pengurus_aset')->firstOrFail()->permissions()->sync($operator);
    }
}
