<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AssetLocation;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\FundingSource;
use App\Models\OrganizationalUnit;
use App\Models\ResponsibleParty;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoSeeder hanya boleh dijalankan pada environment local atau testing.');
        }

        DB::transaction(function (): void {
            $this->call(RbacSeeder::class);

            $tenant = Tenant::query()->updateOrCreate(
                ['village_code' => 'DEMO-CIKADU'],
                ['uuid' => '00000000-0000-4000-8000-000000000001', 'name' => 'Desa Cikadu Demo', 'province' => 'Jawa Barat', 'regency' => 'Cianjur', 'district' => 'Cikadu', 'address' => 'Kecamatan Cikadu, Kabupaten Cianjur', 'timezone' => 'Asia/Jakarta', 'locale' => 'id', 'status' => 'active', 'activated_at' => now()]
            );

            $user = User::query()->updateOrCreate(
                ['email' => 'admin@demo.desatara.local'],
                ['name' => 'Admin Demo DESATARA', 'password' => Hash::make('Demo12345!'), 'email_verified_at' => now()]
            );
            $membership = TenantMembership::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $user->id],
                ['status' => 'active', 'joined_at' => now(), 'valid_from' => now()->subDay()]
            );
            $adminRole = Role::query()->where('scope_type', 'tenant')->where('code', 'tenant_admin')->firstOrFail();
            $membership->roles()->syncWithoutDetaching([$adminRole->id => ['assigned_at' => now()]]);

            $scheme = ClassificationScheme::query()->updateOrCreate(['code' => 'DEMO-ASET-DESA'], ['name' => 'Klasifikasi Aset Desa Demo', 'scope' => 'national', 'status' => 'active']);
            $version = ClassificationVersion::query()->updateOrCreate(['classification_scheme_id' => $scheme->id, 'version_label' => 'demo-2026'], ['effective_from' => '2026-01-01', 'status' => 'published']);
            $classes = collect([
                ['code' => 'TANAH', 'name' => 'Tanah'], ['code' => 'GEDUNG', 'name' => 'Gedung dan Bangunan'], ['code' => 'KENDARAAN', 'name' => 'Kendaraan'], ['code' => 'PERALATAN', 'name' => 'Peralatan dan Mesin'], ['code' => 'ELEKTRONIK', 'name' => 'Peralatan Elektronik'],
            ])->mapWithKeys(function (array $item) use ($version) {
                $model = AssetClassification::query()->updateOrCreate(['classification_version_id' => $version->id, 'code' => $item['code']], ['name' => $item['name'], 'level' => 1, 'status' => 'active']);

                return [$item['code'] => $model];
            });

            $unit = Unit::query()->updateOrCreate(['code' => 'UNIT'], ['name' => 'Unit', 'symbol' => 'unit', 'status' => 'active']);
            $funding = FundingSource::query()->updateOrCreate(['tenant_id' => $tenant->id, 'code' => 'APBDES'], ['name' => 'APB Desa', 'status' => 'active']);
            $office = OrganizationalUnit::query()->updateOrCreate(['tenant_id' => $tenant->id, 'code' => 'PEMDES'], ['name' => 'Pemerintah Desa', 'type' => 'office', 'status' => 'active', 'valid_from' => '2026-01-01']);
            $responsible = ResponsibleParty::query()->updateOrCreate(['tenant_id' => $tenant->id, 'organizational_unit_id' => $office->id], ['party_type' => 'organizational_unit', 'membership_id' => null, 'status' => 'active', 'valid_from' => '2026-01-01']);

            $locations = collect([
                ['KANTOR', 'Kantor Desa', 'building'], ['AULA', 'Aula Desa', 'room'], ['GUDANG', 'Gudang Aset', 'warehouse'], ['POSYANDU', 'Posyandu', 'building'], ['LAPANGAN', 'Area Fasilitas Umum', 'site'],
            ])->mapWithKeys(function (array $item) use ($tenant) {
                $model = AssetLocation::query()->updateOrCreate(['tenant_id' => $tenant->id, 'code' => $item[0]], ['name' => $item[1], 'location_type' => $item[2], 'status' => 'active']);

                return [$item[0] => $model];
            });

            $names = ['Tanah Kantor Desa', 'Tanah Lapang Desa', 'Gedung Kantor Desa', 'Aula Pertemuan', 'Gedung Posyandu', 'Mobil Operasional Desa', 'Sepeda Motor Dinas', 'Laptop Pelayanan', 'Komputer Administrasi', 'Printer Multifungsi', 'Proyektor', 'Kamera Dokumentasi', 'Meja Kerja', 'Kursi Kerja', 'Lemari Arsip', 'Filing Cabinet', 'Genset', 'Pompa Air', 'Mesin Potong Rumput', 'Sound System', 'Speaker Aktif', 'Mikrofon Nirkabel', 'Router Internet', 'Access Point', 'CCTV', 'Televisi Informasi', 'Kulkas Posyandu', 'Timbangan Bayi', 'Tenda Kegiatan', 'Kursi Plastik', 'Meja Lipat', 'Papan Informasi', 'Rak Dokumen', 'UPS', 'Scanner Dokumen', 'Hard Disk Eksternal', 'Dispenser', 'Kipas Angin', 'Alat Pemadam Api', 'Kotak P3K'];
            $conditions = ['good', 'good', 'good', 'fair', 'damaged'];
            $classKeys = ['TANAH', 'TANAH', 'GEDUNG', 'GEDUNG', 'GEDUNG', 'KENDARAAN', 'KENDARAAN'];
            $locationKeys = $locations->keys()->values();

            foreach ($names as $index => $name) {
                $classKey = $classKeys[$index] ?? ($index < 12 ? 'ELEKTRONIK' : 'PERALATAN');
                $value = ($index + 1) * 1750000;
                Asset::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'asset_code' => sprintf('DEMO-%03d', $index + 1)],
                    ['uuid' => (string) Str::uuid(), 'classification_id' => $classes[$classKey]->id, 'register_number' => sprintf('%03d', intdiv($index, 8) + 1), 'name' => $name, 'description' => 'Data demo untuk audit tampilan dan alur DESATARA.', 'acquisition_date' => sprintf('%d-%02d-15', 2019 + ($index % 8), ($index % 12) + 1), 'acquisition_year' => 2019 + ($index % 8), 'acquisition_origin' => 'Pembelian', 'funding_source_id' => $funding->id, 'quantity' => 1, 'unit_id' => $unit->id, 'unit_price' => $value, 'acquisition_value' => $value, 'current_location_id' => $locations[$locationKeys[$index % $locationKeys->count()]]->id, 'current_responsible_party_id' => $responsible->id, 'condition' => $conditions[$index % count($conditions)], 'lifecycle_status' => $index % 9 === 0 ? 'draft' : 'active', 'verification_status' => $index % 4 === 0 ? 'unverified' : 'verified', 'created_by' => $user->id, 'updated_by' => $user->id]
                );
            }
        });

        $this->call(DemoOperationalSeeder::class);
    }
}
