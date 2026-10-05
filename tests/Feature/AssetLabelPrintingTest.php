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

    public function test_tenant_admin_can_prepare_bulk_labels_and_existing_qr_is_rotated(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'Desa Cikadu Demo')->firstOrFail();
        $assets = Asset::query()->where('tenant_id', $tenant->id)->limit(2)->get();
        $first = $assets->firstOrFail();

        [, $oldToken] = app(QrTokenService::class)->issue($tenant, $user, $first);

        $response = $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => $assets->pluck('uuid')->all()]);

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Assets/Labels')
            ->has('labels', 2)
            ->where('tenant.name', 'Desa Cikadu Demo')
            ->where('labels.0.name', $first->name)
            ->where('labels.0.qr_url', fn ($url) => is_string($url) && str_contains($url, '/qr/'))
        );

        $this->assertSame('revoked', $oldToken->fresh()->status);
        $this->assertSame(2, AssetQrToken::query()->where('tenant_id', $tenant->id)->where('status', 'active')->count());
    }

    public function test_label_prepare_rejects_asset_from_another_tenant(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'Desa Cikadu Demo')->firstOrFail();
        $foreignTenant = Tenant::factory()->active()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($foreignTenant)->create();
        app(RoleAssignment::class)->assign($membership, Role::query()->where('code', 'tenant_admin')->where('scope_type', 'tenant')->firstOrFail(), $user);
        $foreignAssetUuid = Asset::query()->where('tenant_id', $tenant->id)->value('uuid');

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $foreignTenant->uuid])
            ->post('/assets/labels/prepare', ['asset_uuids' => [$foreignAssetUuid]])
            ->assertUnprocessable();
    }
}
