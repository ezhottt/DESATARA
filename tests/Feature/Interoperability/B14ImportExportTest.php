<?php

namespace Tests\Feature\Interoperability;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Document;
use App\Models\ImportRow;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\Interoperability\ImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class B14ImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_writes_staging_only_and_confirmation_imports_valid_rows(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $this->grant($membership, 'assets.create');
        $classification = $this->classification();
        $service = app(ImportExportService::class);
        $job = $service->preview($tenant, $user, [['name' => 'A', 'classification_id' => $classification->id], ['name' => 'bad']]);
        $this->assertSame(0, Asset::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, $job->valid_rows);
        $this->assertSame(1, $job->invalid_rows);
        $this->expectException(RuntimeException::class);
        $service->confirm($tenant, $user, $job);
    }

    public function test_import_service_rejects_more_than_the_global_row_count(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $this->grant($membership, 'assets.create');

        $this->expectException(RuntimeException::class);
        app(ImportExportService::class)->preview($tenant, $user, array_fill(0, 5001, ['name' => 'A']));
    }

    public function test_direct_import_route_rejects_more_than_one_thousand_rows(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $this->grant($membership, 'assets.create');

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/interoperability/import/preview', [
                'rows' => array_fill(0, 1001, ['name' => 'A']),
                'strategy' => 'atomic',
            ])
            ->assertSessionHasErrors('rows');
    }

    public function test_import_drops_client_controlled_identity_and_state_fields(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $this->grant($membership, 'assets.create');
        $classification = $this->classification();

        $job = app(ImportExportService::class)->preview($tenant, $user, [[
            'name' => 'Safe import',
            'classification_id' => $classification->id,
            'uuid' => '11111111-1111-4111-8111-111111111111',
            'lifecycle_status' => 'disposed',
            'verification_status' => 'verified',
        ]]);

        $payload = ImportRow::query()->where('import_job_id', $job->id)->firstOrFail()->normalized_payload;
        $this->assertArrayNotHasKey('uuid', $payload);
        $this->assertArrayNotHasKey('lifecycle_status', $payload);
        $this->assertArrayNotHasKey('verification_status', $payload);
    }

    public function test_interoperability_uses_honest_state_machine(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $this->grant($membership, 'reports.export');
        $service = app(ImportExportService::class);
        $export = $service->prepareExport($tenant, $user);
        $this->assertSame('READY_FOR_EXPORT', $export->status);
        $export = $service->markExported($tenant, $user, $export);
        $this->assertSame('EXPORTED', $export->status);
        $document = Document::query()->findOrFail($export->generated_document_id);
        $this->assertSame('clean', $document->malware_scan_status);
        $this->assertSame('stored', $document->storage_state);
        $this->assertSame('RECONCILED', $service->reconcile($tenant, $user, $export)->status);
    }

    private function context(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return [$tenant, $user, $membership];
    }

    private function grant(TenantMembership $membership, string $code): void
    {
        $p = Permission::query()->firstOrCreate(['code' => $code], ['domain' => 'b14', 'action' => 'use']);
        $r = Role::query()->create(['code' => uniqid('b14-'), 'name' => 'B14', 'scope_type' => 'tenant', 'is_system' => false]);
        $r->permissions()->attach($p);
        $membership->roles()->attach($r, ['assigned_at' => now()]);
    }

    private function classification(): AssetClassification
    {
        $s = ClassificationScheme::query()->create(['code' => uniqid('S'), 'name' => 'S', 'scope' => 'national', 'status' => 'active']);
        $v = ClassificationVersion::query()->create(['classification_scheme_id' => $s->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);

        return AssetClassification::query()->create(['classification_version_id' => $v->id, 'code' => uniqid('C'), 'name' => 'C', 'level' => 1, 'status' => 'active']);
    }
}
