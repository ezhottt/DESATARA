<?php

namespace Tests\Feature\Reporting;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Permission;
use App\Models\ReportTemplate;
use App\Models\ReportTemplateVersion;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\TenantOfficial;
use App\Models\Unit;
use App\Models\User;
use App\Services\Reporting\ReportingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class B13ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalized_report_is_a_historical_snapshot_and_revision_keeps_parent_intact(): void
    {
        Storage::fake('private');
        [$tenant, $user, $membership] = $this->memberContext();
        $this->grant($membership, 'reports.view');
        $this->grant($membership, 'reports.export');
        TenantOfficial::query()->create([
            'tenant_id' => $tenant->id,
            'membership_id' => $membership->id,
            'official_type' => 'kepala_desa',
            'position_name' => 'Kepala Desa',
            'authority_code' => 'report.finalize',
            'authority_scope' => ['reports'],
            'valid_from' => today()->subDay(),
            'status' => 'active',
        ]);
        [$period, $version] = $this->reportDefinition($tenant, true);
        $this->asset($tenant, $user, 'Original asset');
        $service = app(ReportingService::class);

        $report = $service->generate($tenant, $user, $period, $version);
        $service->review($tenant, $user, $report);
        $finalized = $service->finalize($tenant, $user, $report);
        $snapshot = $finalized->snapshot;

        $this->assertSame('finalized', $finalized->status);
        $this->assertSame('Original asset', $snapshot->snapshot_payload['dataset'][0]['name']);
        $this->assertSame('Kepala Desa', $snapshot->snapshot_payload['official_context'][0]['position_name']);
        $this->assertNotEmpty($snapshot->snapshot_checksum);
        $this->assertNotEmpty($snapshot->artifact_checksum);

        Asset::query()->firstOrFail()->update(['name' => 'Changed asset']);
        TenantOfficial::query()->firstOrFail()->update(['position_name' => 'Pejabat Baru']);
        $this->assertSame('Original asset', $finalized->refresh()->snapshot->snapshot_payload['dataset'][0]['name']);
        $this->assertSame('Kepala Desa', $finalized->refresh()->snapshot->snapshot_payload['official_context'][0]['position_name']);

        $revision = $service->revise($tenant, $user, $finalized);
        $this->assertSame(2, $revision->revision_number);
        $this->assertSame($finalized->id, $revision->parent_report_id);
        $service->review($tenant, $user, $revision);
        $service->finalize($tenant, $user, $revision);
        $this->assertSame($finalized->id, $revision->refresh()->parent_report_id);
        $this->assertSame('report_artifact', $service->export($tenant, $user, $finalized)->document_type);
        $this->assertSame('finalized', $finalized->refresh()->status);
        $this->assertDatabaseHas('report_snapshots', ['asset_report_id' => $finalized->id]);
    }

    public function test_cross_tenant_report_generation_is_rejected(): void
    {
        [$tenant, $user, $membership] = $this->memberContext();
        $this->grant($membership, 'reports.view');
        [$foreignTenant] = $this->memberContext();
        [$period, $version] = $this->reportDefinition($foreignTenant);

        $this->expectException(AuthorizationException::class);
        app(ReportingService::class)->generate($tenant, $user, $period, $version);
    }

    public function test_published_template_version_cannot_be_changed(): void
    {
        $template = ReportTemplate::query()->create(['code' => 'asset-register', 'name' => 'Asset register', 'status' => 'active']);
        $version = ReportTemplateVersion::query()->create(['report_template_id' => $template->id, 'version' => 1, 'regulatory_context' => [], 'schema_definition' => ['columns' => ['name']], 'status' => 'published', 'published_at' => now()]);

        $this->expectException(LogicException::class);
        $version->update(['schema_definition' => ['columns' => []]]);
    }

    private function reportDefinition(Tenant $tenant, bool $withAuthority = false): array
    {
        $period = app(ReportingService::class)->createPeriod($tenant, $this->tenantUser($tenant), 2026, 'semester', 2, '2026-07-01', '2026-12-31');
        $template = ReportTemplate::query()->create(['code' => uniqid('asset-report-'), 'name' => 'Asset report', 'status' => 'active']);
        $version = ReportTemplateVersion::query()->create(['report_template_id' => $template->id, 'version' => 1, 'regulatory_context' => $withAuthority ? ['authority_code' => 'report.finalize', 'authority_scope' => 'reports'] : [], 'schema_definition' => ['columns' => ['name', 'classification']], 'status' => 'published', 'published_at' => now()]);

        return [$period, $version];
    }

    private function tenantUser(Tenant $tenant): User
    {
        return TenantMembership::query()->where('tenant_id', $tenant->id)->firstOrFail()->user;
    }

    private function memberContext(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return [$tenant, $user, $membership];
    }

    private function grant(TenantMembership $membership, string $code): void
    {
        $permission = Permission::query()->firstOrCreate(['code' => $code], ['domain' => 'reports', 'action' => str($code)->after('.')]);
        $role = Role::query()->create(['code' => uniqid('reporter-'), 'name' => 'Reporter', 'scope_type' => 'tenant', 'is_system' => false]);
        $role->permissions()->attach($permission);
        $membership->roles()->attach($role, ['assigned_at' => now()]);
    }

    private function asset(Tenant $tenant, User $user, string $name): Asset
    {
        $scheme = ClassificationScheme::query()->create(['code' => uniqid('S'), 'name' => 'Scheme', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $classification = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => uniqid('C'), 'name' => 'General', 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->create(['code' => uniqid('U'), 'name' => 'Unit', 'status' => 'active']);

        return Asset::query()->create(['tenant_id' => $tenant->id, 'classification_id' => $classification->id, 'name' => $name, 'quantity' => 1, 'unit_id' => $unit->id, 'condition' => 'good', 'created_by' => $user->id, 'updated_by' => $user->id]);
    }
}
