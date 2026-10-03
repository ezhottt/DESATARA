<?php

namespace Tests\Feature\MasterData;

use App\Models\AssetClassification;
use App\Models\AssetLocation;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\ResponsibleParty;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_classification_parent_must_share_version(): void
    {
        $scheme = ClassificationScheme::query()->create(['code' => 'KIB', 'name' => 'KIB', 'scope' => 'national', 'status' => 'active']);
        $a = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'draft']);
        $b = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '2', 'effective_from' => today(), 'status' => 'draft']);
        $parent = AssetClassification::query()->create(['classification_version_id' => $a->id, 'code' => '01', 'name' => 'Parent', 'level' => 1, 'status' => 'active']);
        $this->expectException(QueryException::class);
        AssetClassification::query()->create(['classification_version_id' => $b->id, 'parent_id' => $parent->id, 'code' => '01.01', 'name' => 'Invalid', 'level' => 2, 'status' => 'active']);
    }

    public function test_location_parent_is_tenant_safe(): void
    {
        $a = Tenant::factory()->active()->create();
        $b = Tenant::factory()->active()->create();
        $parent = AssetLocation::query()->create(['tenant_id' => $a->id, 'code' => 'A', 'name' => 'A', 'location_type' => 'area', 'status' => 'active']);
        $this->expectException(QueryException::class);
        AssetLocation::query()->create(['tenant_id' => $b->id, 'parent_id' => $parent->id, 'code' => 'B', 'name' => 'B', 'location_type' => 'room', 'status' => 'active']);
    }

    public function test_responsible_party_enforces_exactly_one_tenant_owned_reference(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();
        $party = ResponsibleParty::query()->create(['tenant_id' => $tenant->id, 'party_type' => 'membership', 'membership_id' => $membership->id, 'status' => 'active']);
        $this->assertSame($tenant->id, $party->tenant_id);
        $this->expectException(QueryException::class);
        ResponsibleParty::query()->create(['tenant_id' => $tenant->id, 'party_type' => 'membership', 'status' => 'active']);
    }
}
