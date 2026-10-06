<?php

namespace Tests\Feature\Interoperability;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\ImportJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class B26ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_navigation_exposes_import_surface_for_authorized_users(): void
    {
        $shell = file_get_contents(resource_path('js/Layouts/AppShell.vue'));

        $this->assertIsString($shell);
        $this->assertStringContainsString("permissions.value['imports.view'] && { label: 'Impor Data', href: '/imports' }", $shell);
    }

    public function test_csv_upload_maps_indonesian_headers_to_preview_and_commit(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $this->grantMany($membership, ['imports.view', 'imports.create', 'imports.commit']);
        $classification = $this->classification('PERALATAN');
        Unit::query()->create(['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit', 'status' => 'active']);

        $csv = "Nama Barang,Kode Barang,Kode Klasifikasi,Tahun Perolehan,Harga Perolehan,Kondisi\nLaptop Desa,AST-001,PERALATAN,2025,\"12.500.000\",Baik\n";
        $response = $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/imports/assets/preview', ['file' => UploadedFile::fake()->createWithContent('aset-desa.csv', $csv), 'strategy' => 'atomic']);

        $response->assertSessionHasNoErrors();
        $job = ImportJob::query()->firstOrFail();
        $response->assertRedirect('/imports/'.$job->uuid);
        $this->assertSame(1, $job->valid_rows);
        $this->assertSame(0, $job->invalid_rows);
        $this->assertSame('Laptop Desa', $job->rows()->first()->normalized_payload['name']);
        $this->assertSame($classification->id, $job->rows()->first()->normalized_payload['classification_id']);

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/imports/'.$job->uuid.'/commit')->assertRedirect('/imports/'.$job->uuid);

        $asset = Asset::query()->where('tenant_id', $tenant->id)->where('asset_code', 'AST-001')->firstOrFail();
        $this->assertSame('Laptop Desa', $asset->name);
        $this->assertSame('good', $asset->condition);
        $this->assertSame('12500000.00', $asset->acquisition_value);
    }

    public function test_xlsx_upload_is_read_end_to_end(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive is required for XLSX import.');
        }

        [$tenant, $user, $membership] = $this->context();
        $this->grantMany($membership, ['imports.view', 'imports.create', 'imports.commit']);
        $this->classification('PERALATAN');
        Unit::query()->create(['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit', 'status' => 'active']);

        $path = tempnam(sys_get_temp_dir(), 'b26-xlsx-').'.xlsx';
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Aset" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row><c t="inlineStr"><is><t>Nama Barang</t></is></c><c t="inlineStr"><is><t>Kode Barang</t></is></c><c t="inlineStr"><is><t>Kode Klasifikasi</t></is></c><c t="inlineStr"><is><t>Kondisi</t></is></c></row><row><c t="inlineStr"><is><t>Printer Desa</t></is></c><c t="inlineStr"><is><t>XLSX-001</t></is></c><c t="inlineStr"><is><t>PERALATAN</t></is></c><c t="inlineStr"><is><t>Baik</t></is></c></row></sheetData></worksheet>');
        $zip->close();

        $file = new UploadedFile($path, 'aset-desa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $response = $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])->post('/imports/assets/preview', ['file' => $file, 'strategy' => 'atomic']);
        $job = ImportJob::query()->firstOrFail();
        $response->assertRedirect('/imports/'.$job->uuid);
        $this->assertSame(1, $job->valid_rows);
        $this->assertSame('Printer Desa', $job->rows()->first()->normalized_payload['name']);

        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])->post('/imports/'.$job->uuid.'/commit')->assertRedirect('/imports/'.$job->uuid);
        $this->assertDatabaseHas('assets', ['tenant_id' => $tenant->id, 'asset_code' => 'XLSX-001', 'name' => 'Printer Desa']);
    }

    public function test_legacy_xls_returns_friendly_validation_error(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $this->grantMany($membership, ['imports.view', 'imports.create']);

        $response = $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->from('/imports')
            ->post('/imports/assets/preview', [
                'file' => UploadedFile::fake()->create('aset-lama.xls', 10, 'application/vnd.ms-excel'),
                'strategy' => 'atomic',
            ]);

        $response->assertRedirect('/imports')->assertSessionHasErrors('file');
    }

    public function test_preview_flags_existing_asset_code_as_duplicate_without_overwriting(): void
    {
        [$tenant, $user, $membership] = $this->context();
        $this->grantMany($membership, ['imports.view', 'imports.create']);
        $classification = $this->classification('ELEKTRONIK');
        $unit = Unit::query()->create(['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit', 'status' => 'active']);
        Asset::query()->create(['tenant_id' => $tenant->id, 'uuid' => fake()->uuid(), 'classification_id' => $classification->id, 'asset_code' => 'AST-001', 'name' => 'Aset Lama', 'quantity' => 1, 'unit_id' => $unit->id, 'condition' => 'good', 'lifecycle_status' => 'active', 'verification_status' => 'verified', 'created_by' => $user->id, 'updated_by' => $user->id]);

        $file = UploadedFile::fake()->createWithContent('duplikat.csv', "Nama Barang,Kode Barang,Kode Klasifikasi\nAset Baru,AST-001,ELEKTRONIK\n");
        $this->actingAs($user)->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/imports/assets/preview', ['file' => $file, 'strategy' => 'atomic'])->assertRedirect();

        $job = ImportJob::query()->firstOrFail();
        $this->assertSame(0, $job->valid_rows);
        $this->assertSame(1, $job->invalid_rows);
        $this->assertDatabaseHas('import_errors', ['import_job_id' => $job->id, 'error_code' => 'duplicate_asset_code']);
        $this->assertSame('Aset Lama', Asset::query()->where('tenant_id', $tenant->id)->where('asset_code', 'AST-001')->value('name'));
    }

    private function context(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return [$tenant, $user, $membership];
    }

    /** @param list<string> $codes */
    private function grantMany(TenantMembership $membership, array $codes): void
    {
        $role = Role::query()->create(['code' => uniqid('b26-'), 'name' => 'B26', 'scope_type' => 'tenant', 'is_system' => false]);
        foreach ($codes as $code) {
            [$domain, $action] = explode('.', $code, 2);
            $permission = Permission::query()->firstOrCreate(['code' => $code], ['domain' => $domain, 'action' => $action]);
            $role->permissions()->attach($permission);
        }
        $membership->roles()->attach($role, ['assigned_at' => now()]);
    }

    private function classification(string $code): AssetClassification
    {
        $scheme = ClassificationScheme::query()->create(['code' => uniqid('S'), 'name' => 'S', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);

        return AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => $code, 'name' => $code, 'level' => 1, 'status' => 'active']);
    }
}
