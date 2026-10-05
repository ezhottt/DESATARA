<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetQrToken;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\Evidence\QrTokenService;
use App\Support\Authorization\RoleAssignment;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AssetLabelPrintingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_prepare_bulk_labels_with_stable_public_uuid_qr(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'Desa Cikadu Demo')->firstOrFail();
        $assets = Asset::query()->where('tenant_id', $tenant->id)->with('classification')->limit(2)->get();
        $first = $assets->firstOrFail();

        [, $oldToken] = app(QrTokenService::class)->issue($tenant, $user, $first);

        $response = $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => $assets->pluck('uuid')->all()]);

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Assets/Labels')
            ->has('labels', 2)
            ->where('tenant.name', 'Desa Cikadu Demo')
            ->where('tenant.village_code', 'DEMO-CIKADU')
            ->where('labels.0.name', $first->name)
            ->where('labels.0.item_code', $first->classification->code)
            ->where('labels.0.acquisition_year', $first->acquisition_year)
            ->where('labels.0.qr_url', fn ($url) => is_string($url) && str_contains($url, '/verifikasi-aset/'.$first->uuid))
        );

        $this->assertSame('active', $oldToken->fresh()->status);
        $this->assertSame(1, AssetQrToken::query()->where('tenant_id', $tenant->id)->where('status', 'active')->count());
    }

    public function test_public_uuid_verification_is_allowlisted_and_omits_sensitive_fields(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $tenant = Tenant::query()->where('name', 'Desa Cikadu Demo')->firstOrFail();
        $asset = Asset::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $response = $this->get('/verifikasi-aset/'.$asset->uuid)
            ->assertOk()
            ->assertJsonStructure(['name', 'inventory_code', 'acquisition_year', 'status', 'village_name']);

        $this->assertSame(
            ['name', 'inventory_code', 'acquisition_year', 'status', 'village_name'],
            array_keys($response->json())
        );
        $response->assertJsonMissingPath('acquisition_value')
            ->assertJsonMissingPath('documents')
            ->assertJsonMissingPath('created_by')
            ->assertJsonMissingPath('audit_log');
    }

    public function test_member_without_documents_manage_cannot_prepare_labels(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $tenant = Tenant::query()->where('name', 'Desa Cikadu Demo')->firstOrFail();
        $asset = Asset::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $viewer = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($viewer)->for($tenant)->create();
        app(RoleAssignment::class)->assign($membership, Role::query()->where('code', 'viewer')->where('scope_type', 'tenant')->firstOrFail(), $viewer);

        $this->actingAs($viewer)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => [$asset->uuid]])
            ->assertForbidden();
    }

    public function test_label_prepare_rejects_asset_from_another_tenant(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'Desa Cikadu Demo')->firstOrFail();
        $foreignTenant = Tenant::factory()->active()->create(['village_code' => 'OTHER']);
        $membership = TenantMembership::factory()->active()->for($user)->for($foreignTenant)->create();
        app(RoleAssignment::class)->assign($membership, Role::query()->where('code', 'tenant_admin')->where('scope_type', 'tenant')->firstOrFail(), $user);
        $foreignAssetUuid = Asset::query()->where('tenant_id', $tenant->id)->value('uuid');

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $foreignTenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => [$foreignAssetUuid]])
            ->assertUnprocessable();
    }
}
