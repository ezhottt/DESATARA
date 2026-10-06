<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use App\Support\Authorization\RoleAssignment;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssetLabelRouteValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_redirects_back_with_specific_error_when_village_code_is_missing(): void
    {
        [$tenant, $user, $asset] = $this->labelReadyFixture();
        $tenant->forceFill(['village_code' => null])->save();

        $this->assertPrintRejected($tenant, $user, $asset, 'Label belum dapat dicetak karena Kode Wilayah Desa belum tersedia.');
    }

    public function test_print_redirects_back_with_specific_error_when_item_code_is_missing(): void
    {
        [$tenant, $user, $asset] = $this->labelReadyFixture();
        $asset->classification->forceFill(['code' => ''])->save();

        $this->assertPrintRejected($tenant, $user, $asset, 'Label belum dapat dicetak karena Kode Barang belum tersedia.');
    }

    public function test_print_redirects_back_with_specific_error_when_acquisition_year_source_is_missing(): void
    {
        [$tenant, $user, $asset] = $this->labelReadyFixture([], ['acquisition_date' => null]);

        $this->assertPrintRejected($tenant, $user, $asset, 'Label belum dapat dicetak karena Tahun Perolehan belum tersedia.');
    }

    public function test_print_redirects_back_with_specific_error_when_register_is_missing(): void
    {
        [$tenant, $user, $asset] = $this->labelReadyFixture([], ['register_number' => null]);

        $this->assertPrintRejected($tenant, $user, $asset, 'Label belum dapat dicetak karena Nomor Register belum tersedia.');
    }

    private function assertPrintRejected(Tenant $tenant, User $user, Asset $asset, string $message): void
    {
        $detailUrl = '/assets/'.$asset->uuid;

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->from($detailUrl)
            ->post('/assets/labels/prepare', ['asset_uuids' => [$asset->uuid]])
            ->assertRedirect($detailUrl)
            ->assertSessionHasErrors(['asset' => $message]);
    }

    /**
     * @return array{Tenant, User, Asset}
     */
    private function labelReadyFixture(array $tenantOverrides = [], array $assetOverrides = []): array
    {
        $tenant = Tenant::factory()->active()->create(array_replace([
            'name' => 'Desa Cikadu',
            'village_code' => '32.03.26.2007',
        ], $tenantOverrides));
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        $this->seed(RbacSeeder::class);
        $role = Role::query()->where('code', 'tenant_admin')->where('scope_type', 'tenant')->firstOrFail();
        app(RoleAssignment::class)->assign($membership, $role, $user);

        $scheme = ClassificationScheme::query()->create([
            'code' => 'LABEL-ROUTE-'.uniqid(),
            'name' => 'Master Label',
            'scope' => 'national',
            'status' => 'active',
        ]);
        $version = ClassificationVersion::query()->create([
            'classification_scheme_id' => $scheme->id,
            'version_label' => '2026',
            'effective_from' => '2026-01-01',
            'status' => 'published',
        ]);
        $classification = AssetClassification::query()->create([
            'classification_version_id' => $version->id,
            'code' => '1.3.2.10.01.02.003',
            'name' => 'Mesin Potong Rumput',
            'level' => 7,
            'status' => 'active',
        ]);
        $unit = Unit::query()->create([
            'code' => 'UNIT-'.uniqid(),
            'name' => 'Unit',
            'status' => 'active',
        ]);

        $asset = Asset::query()->create(array_replace([
            'tenant_id' => $tenant->id,
            'classification_id' => $classification->id,
            'register_number' => '003',
            'name' => 'Mesin Potong Rumput',
            'acquisition_date' => '2021-08-10',
            'acquisition_year' => 2021,
            'quantity' => 1,
            'unit_id' => $unit->id,
            'condition' => 'good',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $assetOverrides))->load('classification');

        return [$tenant, $user, $asset];
    }
}
