<?php

namespace Tests\Feature\Interoperability;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ImportJob;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class AssetImportIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_csv_contract_uses_master_item_code_derives_year_and_generates_nup(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('village_code', '32.03.26.2002')->firstOrFail();
        $classification = AssetClassification::query()->where('status', 'active')->firstOrFail();

        $csv = implode("\n", [
            'Nama Barang,Kode Barang,NUP,Tanggal Perolehan,Kode Internal,Jumlah,Satuan,Kondisi',
            'Laptop Import,'.$classification->code.',,15-03-2026,INTERNAL-IMPORT-1,1,UNIT,Baik',
        ])."\n";

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/imports/assets/preview', [
                'file' => UploadedFile::fake()->createWithContent('aset-current.csv', $csv),
                'strategy' => 'atomic',
            ])
            ->assertRedirect();

        $job = ImportJob::query()->latest('id')->firstOrFail();
        $this->assertSame(1, $job->valid_rows);
        $row = $job->rows()->firstOrFail();
        $this->assertSame($classification->id, $row->normalized_payload['classification_id']);
        $this->assertSame('INTERNAL-IMPORT-1', $row->normalized_payload['asset_code']);
        $this->assertSame('2026-03-15', $row->normalized_payload['acquisition_date']);
        $this->assertSame(2026, $row->normalized_payload['acquisition_year']);

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/imports/'.$job->uuid.'/commit')
            ->assertRedirect();

        $asset = Asset::query()->where('tenant_id', $tenant->id)->where('name', 'Laptop Import')->firstOrFail();
        $this->assertMatchesRegularExpression('/^\d{3,}$/', (string) $asset->register_number);
        $this->assertSame($classification->id, $asset->classification_id);
        $this->assertSame(2026, $asset->acquisition_year);
    }

    public function test_import_normalizes_numeric_nup_and_preserves_legacy_aggregate_without_auto_identity(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('village_code', '32.03.26.2002')->firstOrFail();
        $classification = AssetClassification::query()->where('status', 'active')->firstOrFail();

        $csv = implode('
', [
            'Nama Barang,Kode Barang,NUP,Tanggal Perolehan,Kode Internal,Jumlah,Satuan,Kondisi',
            'Laptop Canonical,'.$classification->code.',7,15-03-2030,INTERNAL-IMPORT-7,1,UNIT,Baik',
            'Laptop Aggregate,'.$classification->code.',,15-03-2030,INTERNAL-IMPORT-8,2,UNIT,Baik',
        ]).'
';

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/imports/assets/preview', [
                'file' => UploadedFile::fake()->createWithContent('aset-identity.csv', $csv),
                'strategy' => 'partial',
            ])
            ->assertRedirect();

        $job = ImportJob::query()->latest('id')->firstOrFail();
        $this->assertSame(2, $job->valid_rows);
        $this->assertSame(0, $job->invalid_rows);
        $this->assertSame('007', $job->rows()->orderBy('row_number')->firstOrFail()->normalized_payload['register_number']);

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/imports/'.$job->uuid.'/commit')
            ->assertRedirect();

        $canonical = Asset::query()->where('tenant_id', $tenant->id)->where('name', 'Laptop Canonical')->firstOrFail();
        $aggregate = Asset::query()->where('tenant_id', $tenant->id)->where('name', 'Laptop Aggregate')->firstOrFail();
        $this->assertSame('007', $canonical->register_number);
        $this->assertNull($aggregate->register_number);
        $this->assertSame('2.0000', (string) $aggregate->quantity);
    }
}
