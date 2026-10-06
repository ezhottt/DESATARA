<?php

namespace Database\Seeders;

use App\Models\ApprovalRequest;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\AssetReport;
use App\Models\InventoryDiscrepancy;
use App\Models\InventoryItem;
use App\Models\InventorySession;
use App\Models\ReportingPeriod;
use App\Models\ReportTemplate;
use App\Models\ReportTemplateVersion;
use App\Models\SubjectType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowVersion;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoOperationalSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoOperationalSeeder hanya boleh dijalankan pada environment local atau testing.');
        }

        $tenant = Tenant::query()->where('village_code', 'DEMO-CIKADU')->firstOrFail();
        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $assets = Asset::query()->where('tenant_id', $tenant->id)->orderBy('id')->get();

        foreach ($assets->take(3) as $index => $asset) {
            AssetMaintenance::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'asset_id' => $asset->id, 'maintenance_type' => 'preventive'],
                ['planned_at' => now()->addDays(7 + $index), 'planned_cost' => 350000 + ($index * 150000), 'condition_before' => $asset->condition, 'status' => $index === 0 ? 'in_progress' : 'planned', 'notes' => 'Jadwal pemeliharaan demo.', 'created_by' => $user->id, 'updated_by' => $user->id]
            );
        }

        $inventory = InventorySession::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Inventarisasi Semester II 2026'],
            ['period_label' => 'Semester II 2026', 'scope_definition' => [], 'reference_at' => now(), 'status' => 'in_progress', 'started_at' => now()->subDay(), 'created_by' => $user->id]
        );
        $asset = $assets->first();
        $item = InventoryItem::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'inventory_session_id' => $inventory->id, 'asset_id' => $asset->id],
            ['asset_uuid_snapshot' => $asset->uuid, 'asset_code_snapshot' => $asset->asset_code, 'register_number_snapshot' => $asset->register_number, 'asset_name_snapshot' => $asset->name, 'classification_id_snapshot' => $asset->classification_id, 'expected_location_id' => $asset->current_location_id, 'expected_condition' => $asset->condition, 'lifecycle_status_snapshot' => $asset->lifecycle_status, 'verification_result' => 'condition_mismatch', 'verified_by' => $user->id, 'verified_at' => now(), 'snapshot_payload' => []]
        );
        InventoryDiscrepancy::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'inventory_session_id' => $inventory->id, 'inventory_item_id' => $item->id, 'asset_id' => $asset->id],
            ['discrepancy_type' => 'condition_mismatch', 'description' => 'Kondisi fisik berbeda dengan data register.', 'status' => 'open', 'proposed_resolution' => 'condition_update']
        );

        $subjectType = SubjectType::query()->updateOrCreate(['code' => 'demo_asset'], ['name' => 'Aset Demo', 'model_key' => Asset::class, 'status' => 'active']);
        $workflow = WorkflowDefinition::query()->updateOrCreate(['code' => 'DEMO-ASSET-APPROVAL'], ['name' => 'Persetujuan Aset Demo', 'status' => 'active']);
        $version = WorkflowVersion::query()->firstOrCreate(['workflow_definition_id' => $workflow->id, 'version' => 1], ['definition' => ['steps' => []], 'effective_from' => today(), 'status' => 'published', 'published_at' => now()]);
        $instance = WorkflowInstance::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'subject_type_id' => $subjectType->id, 'subject_id' => $assets[1]->id],
            ['workflow_version_id' => $version->id, 'current_state' => 'PENDING', 'started_by' => $user->id, 'started_at' => now()->subHours(2)]
        );
        ApprovalRequest::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'workflow_instance_id' => $instance->id],
            ['subject_type_id' => $subjectType->id, 'subject_id' => $assets[1]->id, 'status' => 'pending', 'requested_by' => $user->id, 'submitted_at' => now()->subHours(2)]
        );

        $period = ReportingPeriod::query()->updateOrCreate(['tenant_id' => $tenant->id, 'year' => 2026, 'period_type' => 'year'], ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deadline' => '2027-01-31', 'status' => 'open']);
        $template = ReportTemplate::query()->updateOrCreate(['code' => 'DEMO-REGISTER'], ['name' => 'Register Aset Desa', 'description' => 'Template laporan demo.', 'status' => 'active']);
        $templateVersion = ReportTemplateVersion::query()->firstOrCreate(['report_template_id' => $template->id, 'version' => 1], ['regulatory_context' => [], 'schema_definition' => [], 'effective_from' => '2026-01-01', 'status' => 'published', 'published_at' => now()]);
        AssetReport::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'reporting_period_id' => $period->id, 'report_template_version_id' => $templateVersion->id, 'revision_number' => 1],
            ['status' => 'draft', 'generated_by' => $user->id, 'generated_at' => now()]
        );
    }
}
