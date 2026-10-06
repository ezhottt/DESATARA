<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssetRegistrationIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_registration_derives_year_and_generates_immutable_nup(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('village_code', 'DEMO-CIKADU')->firstOrFail();
        $classification = AssetClassification::query()->where('status', 'active')->firstOrFail();
        $unit = Unit::query()->where('status', 'active')->firstOrFail();

        $existingMax = Asset::query()
            ->where('tenant_id', $tenant->id)
            ->where('classification_id', $classification->id)
            ->where('acquisition_year', 2026)
            ->whereNotNull('register_number')
            ->pluck('register_number')
            ->filter(fn ($value) => is_string($value) && ctype_digit($value))
            ->map(fn (string $value) => (int) $value)
            ->max() ?? 0;

        $payload = [
            'classification_id' => $classification->id,
            'asset_code' => 'INTERNAL-NEW',
            'register_number' => '999',
            'name' => 'Laptop Registrasi Baru',
            'acquisition_date' => '2026-03-15',
            'quantity' => 1,
            'unit_id' => $unit->id,
            'condition' => 'good',
        ];

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets', $payload)
            ->assertRedirect();

        $first = Asset::query()->where('tenant_id', $tenant->id)->where('name', 'Laptop Registrasi Baru')->firstOrFail();
        $this->assertSame(2026, $first->acquisition_year);
        $this->assertSame(str_pad((string) ($existingMax + 1), 3, '0', STR_PAD_LEFT), $first->register_number);
        $this->assertNotSame('999', $first->register_number);

        $payload['name'] = 'Laptop Registrasi Kedua';
        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets', $payload)
            ->assertRedirect();

        $this->assertSame(
            str_pad((string) ($existingMax + 2), 3, '0', STR_PAD_LEFT),
            Asset::query()->where('name', 'Laptop Registrasi Kedua')->value('register_number')
        );
    }

    public function test_interactive_registration_is_one_physical_unit_per_record(): void
    {
        $this->withoutVite();
        $this->seed(DemoSeeder::class);

        $user = User::query()->where('email', 'admin@demo.desatara.local')->firstOrFail();
        $tenant = Tenant::query()->where('village_code', 'DEMO-CIKADU')->firstOrFail();
        $classification = AssetClassification::query()->where('status', 'active')->firstOrFail();
        $unit = Unit::query()->where('status', 'active')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['active_tenant_uuid' => $tenant->uuid])
            ->post('/assets', [
                'classification_id' => $classification->id,
                'name' => 'Batch Fisik',
                'acquisition_date' => '2026-03-15',
                'quantity' => 2,
                'unit_id' => $unit->id,
                'condition' => 'good',
            ])
            ->assertSessionHasErrors('quantity');
    }
}
