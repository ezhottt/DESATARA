<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AssetQrToken;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use App\Services\Evidence\QrTokenService;
use App\Support\Authorization\RoleAssignment;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AssetLabelPrintingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_prepare_bulk_labels_with_official_identity_and_stable_public_uuid_qr(): void
    {
        $this->withoutVite();
        [$tenant, $user, $assets] = $this->labelReadyContext(2);
        $first = $assets->firstOrFail();

        [, $oldToken] = app(QrTokenService::class)->issue($tenant, $user, $first);

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => $assets->pluck('uuid')->all()])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assets/Labels')
                ->has('labels', 2)
                ->where('labels.0.village_name', 'CIKADU')
                ->where('labels.0.village_code', '32.03.26.2007')
                ->where('labels.0.item_code', '1.3.2.10.01.02.003')
                ->where('labels.0.acquisition_year', 2021)
                ->where('labels.0.register_number', '001')
                ->where('labels.0.inventory_code', '32.03.26.2007 / 1.3.2.10.01.02.003 / 2021 / 001')
                ->where('labels.0.qr_url', fn ($url) => is_string($url) && str_contains($url, '/verifikasi-aset/'.$first->uuid))
            );

        $this->assertSame('active', $oldToken->fresh()->status);
        $this->assertSame(1, AssetQrToken::query()->where('tenant_id', $tenant->id)->where('status', 'active')->count());
    }

    public function test_demo_slug_and_demo_category_are_blocked_instead_of_printed_as_inventory_identity(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('village_code', 'DEMO-CIKADU')->firstOrFail();
        $asset = Asset::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => [$asset->uuid]])
            ->assertSessionHasErrors('asset');

        $this->assertSame('DEMO-CIKADU', $tenant->village_code);
        $this->assertContains($asset->classification->code, ['TANAH', 'GEDUNG', 'KENDARAAN', 'PERALATAN', 'ELEKTRONIK']);
    }

    public function test_public_uuid_verification_is_allowlisted_points_to_correct_asset_and_omits_sensitive_fields(): void
    {
        $this->withoutVite();
        [$tenant, , $assets] = $this->labelReadyContext(1);
        $asset = $assets->firstOrFail();

        $response = $this->get('/verifikasi-aset/'.$asset->uuid)
            ->assertOk()
            ->assertJsonStructure(['name', 'inventory_code', 'acquisition_year', 'status', 'village_name']);

        $this->assertSame($asset->name, $response->json('name'));
        $this->assertSame('32.03.26.2007 / 1.3.2.10.01.02.003 / 2021 / 001', $response->json('inventory_code'));
        $this->assertSame(
            ['name', 'inventory_code', 'acquisition_year', 'status', 'village_name'],
            array_keys($response->json())
        );
        $response->assertJsonMissingPath('acquisition_value')
            ->assertJsonMissingPath('documents')
            ->assertJsonMissingPath('created_by')
            ->assertJsonMissingPath('audit_log')
            ->assertJsonMissingPath('tenant_id');
    }

    public function test_member_without_documents_manage_cannot_prepare_labels(): void
    {
        $this->withoutVite();
        [$tenant, , $assets] = $this->labelReadyContext(1);
        $asset = $assets->firstOrFail();
        $viewer = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($viewer)->for($tenant)->create();
        app(RoleAssignment::class)->assign(
            $membership,
            Role::query()->where('code', 'viewer')->where('scope_type', 'tenant')->firstOrFail(),
            $viewer
        );

        $this->actingAs($viewer)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => [$asset->uuid]])
            ->assertForbidden();
    }

    public function test_label_prepare_rejects_asset_from_another_tenant(): void
    {
        $this->withoutVite();
        [$tenant, $user, $assets] = $this->labelReadyContext(1);
        $foreignTenant = Tenant::factory()->active()->create(['village_code' => '32.03.26.2008']);
        $membership = TenantMembership::factory()->active()->for($user)->for($foreignTenant)->create();
        app(RoleAssignment::class)->assign(
            $membership,
            Role::query()->where('code', 'tenant_admin')->where('scope_type', 'tenant')->firstOrFail(),
            $user
        );

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $foreignTenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => [$assets->firstOrFail()->uuid]])
            ->assertUnprocessable();

        $this->assertSame($tenant->id, $assets->firstOrFail()->tenant_id);
    }

    /**
     * @return array{Tenant, User, Collection<int, Asset>}
     */
    private function labelReadyContext(int $count): array
    {
        $this->seed(DemoSeeder::class);

        $tenant = Tenant::query()->where('village_code', 'DEMO-CIKADU')->firstOrFail();
        $tenant->forceFill([
            'name' => 'Desa Cikadu Demo',
            'village_code' => '32.03.26.2007',
        ])->save();

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $scheme = ClassificationScheme::query()->create([
            'code' => 'OFFICIAL-LABEL-TEST',
            'name' => 'Master Kode Barang Test',
            'scope' => 'national',
            'status' => 'active',
        ]);
        $version = ClassificationVersion::query()->create([
            'classification_scheme_id' => $scheme->id,
            'version_label' => 'label-test',
            'effective_from' => '2021-01-01',
            'status' => 'published',
        ]);
        $classification = AssetClassification::query()->create([
            'classification_version_id' => $version->id,
            'code' => '1.3.2.10.01.02.003',
            'name' => 'Mesin Potong Rumput',
            'level' => 7,
            'status' => 'active',
        ]);
        $unit = Unit::query()->where('status', 'active')->firstOrFail();

        $assets = collect();
        for ($i = 1; $i <= $count; $i++) {
            $assets->push(Asset::query()->create([
                'tenant_id' => $tenant->id,
                'classification_id' => $classification->id,
                'asset_code' => 'LABEL-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'register_number' => str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => $i === 1 ? 'Mesin Potong Rumput' : 'Aset Label '.$i,
                'acquisition_date' => '2021-08-10',
                'acquisition_year' => 2021,
                'quantity' => 1,
                'unit_id' => $unit->id,
                'condition' => 'good',
                'lifecycle_status' => 'active',
                'verification_status' => 'verified',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ])->load('classification'));
        }

        return [$tenant, $user, $assets];
    }
}
