<?php

namespace Tests\Feature\Assets;

use App\Actions\Assets\ManageHistoricalAssetState;
use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AssetClassificationAssignment;
use App\Models\AssetConditionEvent;
use App\Models\AssetCorrection;
use App\Models\AssetLifecycleEvent;
use App\Models\AssetLocation;
use App\Models\AssetMutation;
use App\Models\AssetResponsibilityAssignment;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\OrganizationalUnit;
use App\Models\ResponsibleParty;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class HistoricalAssetStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_initializes_authoritative_history_from_current_projections(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);

        $this->assertDatabaseHas('asset_classification_assignments', [
            'tenant_id' => $fixture['tenant']->id,
            'asset_id' => $asset->id,
            'classification_id' => $fixture['classification']->id,
            'assignment_type' => 'initial',
            'valid_until' => null,
            'workflow_instance_id' => null,
        ]);
        $this->assertDatabaseHas('asset_mutations', [
            'tenant_id' => $fixture['tenant']->id,
            'asset_id' => $asset->id,
            'origin_location_id' => null,
            'destination_location_id' => $fixture['location']->id,
            'mutation_type' => 'initial_placement',
            'status' => 'completed',
            'workflow_instance_id' => null,
        ]);
        $this->assertDatabaseHas('asset_responsibility_assignments', [
            'tenant_id' => $fixture['tenant']->id,
            'asset_id' => $asset->id,
            'responsible_party_id' => $fixture['responsible_party']->id,
            'valid_until' => null,
            'workflow_instance_id' => null,
        ]);
        $this->assertDatabaseHas('asset_condition_events', ['asset_id' => $asset->id, 'previous_condition' => null, 'new_condition' => 'good', 'source_type' => 'registration']);
        $this->assertDatabaseHas('asset_lifecycle_events', ['asset_id' => $asset->id, 'from_status' => null, 'to_status' => 'draft', 'transition_type' => 'registration']);
    }

    public function test_reclassification_preserves_history_and_rejects_a_stale_writer_without_partial_changes(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $replacement = $this->classification('2');
        $effectiveAt = now()->addMinute();
        $history = app(ManageHistoricalAssetState::class);

        $history->reclassify($asset, $replacement->id, $fixture['user']->id, $effectiveAt, 1, 'Correction of code');

        $this->assertSame($replacement->id, $asset->fresh()->classification_id);
        $this->assertSame(2, $asset->fresh()->lock_version);
        $this->assertSame(1, AssetClassificationAssignment::query()->where('asset_id', $asset->id)->whereNull('valid_until')->count());
        $this->assertDatabaseHas('asset_classification_assignments', ['asset_id' => $asset->id, 'classification_id' => $fixture['classification']->id, 'valid_until' => $effectiveAt]);

        try {
            $history->reclassify($asset, $fixture['classification']->id, $fixture['user']->id, now()->addMinutes(2), 1);
            $this->fail('Stale reclassification was accepted.');
        } catch (RuntimeException) {
            $this->assertSame(2, AssetClassificationAssignment::query()->where('asset_id', $asset->id)->count());
            $this->assertSame($replacement->id, $asset->fresh()->classification_id);
        }
    }

    public function test_location_mutation_is_atomic_tenant_safe_and_idempotent(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $destination = AssetLocation::query()->create(['tenant_id' => $fixture['tenant']->id, 'code' => 'L2', 'name' => 'Archive', 'location_type' => 'room', 'status' => 'active']);
        $history = app(ManageHistoricalAssetState::class);

        $first = $history->move($asset, $destination->id, $fixture['user']->id, now()->addMinute(), 1, 'relocation', 'More secure room', 'move-1');
        $retry = $history->move($asset, $destination->id, $fixture['user']->id, $first->effective_at, 1, 'relocation', 'More secure room', 'move-1');

        $this->assertTrue($first->is($retry));
        $this->assertSame(2, AssetMutation::query()->where('asset_id', $asset->id)->count());
        $this->assertSame($destination->id, $asset->fresh()->current_location_id);

        try {
            $history->move($asset, $fixture['location']->id, $fixture['user']->id, now()->addMinutes(2), 1, 'relocation', null, 'move-stale');
            $this->fail('Stale mutation was accepted.');
        } catch (RuntimeException) {
            $this->assertSame(2, AssetMutation::query()->where('asset_id', $asset->id)->count());
            $this->assertSame($destination->id, $asset->fresh()->current_location_id);
        }

        $otherTenant = Tenant::factory()->active()->create();
        $foreignLocation = AssetLocation::query()->create(['tenant_id' => $otherTenant->id, 'code' => 'L2', 'name' => 'Foreign', 'location_type' => 'room', 'status' => 'active']);

        try {
            $history->move($asset, $foreignLocation->id, $fixture['user']->id, now()->addMinutes(2), 2, 'relocation', null, 'move-foreign');
            $this->fail('Cross-tenant mutation was accepted.');
        } catch (QueryException) {
            $this->assertSame(2, AssetMutation::query()->where('asset_id', $asset->id)->count());
            $this->assertSame($destination->id, $asset->fresh()->current_location_id);
            $this->assertSame(2, $asset->fresh()->lock_version);
        }
    }

    public function test_responsibility_change_preserves_exactly_one_open_assignment(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $unit = OrganizationalUnit::query()->create(['tenant_id' => $fixture['tenant']->id, 'code' => 'UNIT-2', 'name' => 'Unit Two', 'type' => 'office', 'status' => 'active']);
        $replacement = ResponsibleParty::query()->create(['tenant_id' => $fixture['tenant']->id, 'party_type' => 'organizational_unit', 'organizational_unit_id' => $unit->id, 'status' => 'active']);
        $effectiveAt = now()->addMinute();

        app(ManageHistoricalAssetState::class)->assignResponsibility($asset, $replacement->id, $fixture['user']->id, $effectiveAt, 1, 'handover');

        $this->assertSame($replacement->id, $asset->fresh()->current_responsible_party_id);
        $this->assertSame(1, AssetResponsibilityAssignment::query()->where('asset_id', $asset->id)->whereNull('valid_until')->count());
        $this->assertDatabaseHas('asset_responsibility_assignments', ['asset_id' => $asset->id, 'responsible_party_id' => $fixture['responsible_party']->id, 'valid_until' => $effectiveAt]);
    }

    public function test_condition_and_lifecycle_events_preserve_history_and_direct_projection_updates_fail(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $history = app(ManageHistoricalAssetState::class);

        $history->changeCondition($asset, 'damaged', $fixture['user']->id, now()->addMinute(), 1, 'inspection', 'Physical finding', 'condition-1');
        $history->transitionLifecycle($asset->fresh(), 'active', $fixture['user']->id, now()->addMinutes(2), 2, 'activate', 'Validated', 'lifecycle-1');

        $this->assertSame(['good', 'damaged'], AssetConditionEvent::query()->where('asset_id', $asset->id)->orderBy('id')->pluck('new_condition')->all());
        $this->assertSame(['draft', 'active'], AssetLifecycleEvent::query()->where('asset_id', $asset->id)->orderBy('id')->pluck('to_status')->all());
        $this->assertSame('damaged', $asset->fresh()->condition);
        $this->assertSame('active', $asset->fresh()->lifecycle_status);

        $this->expectException(QueryException::class);
        $asset->fresh()->update(['lifecycle_status' => 'disposed']);
    }

    public function test_controlled_correction_records_evidence_and_routes_historical_fields_through_events(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $history = app(ManageHistoricalAssetState::class);
        $appliedAt = now()->addMinute();

        $correction = $history->correct(
            $asset,
            ['name' => 'Corrected Asset', 'condition' => 'minor_damage'],
            'Validated transcription error',
            $fixture['user']->id,
            $appliedAt,
            1,
            'MEMO-01',
            'correction-1',
        );
        $retry = $history->correct($asset, ['name' => 'Corrected Asset', 'condition' => 'minor_damage'], 'Validated transcription error', $fixture['user']->id, $appliedAt, 1, 'MEMO-01', 'correction-1');

        $this->assertTrue($correction->is($retry));
        $this->assertSame(1, AssetCorrection::query()->where('asset_id', $asset->id)->count());
        $this->assertEquals(['condition' => true, 'name' => true], $correction->corrected_fields);
        $this->assertEquals(['condition' => 'good', 'name' => 'Synthetic Asset'], $correction->before_values);
        $this->assertEquals(['condition' => 'minor_damage', 'name' => 'Corrected Asset'], $correction->after_values);
        $this->assertSame('Corrected Asset', $asset->fresh()->name);
        $this->assertSame('minor_damage', $asset->fresh()->condition);
        $this->assertSame(2, AssetConditionEvent::query()->where('asset_id', $asset->id)->count());
    }

    public function test_history_rows_are_immutable_after_their_allowed_interval_close(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $event = AssetConditionEvent::query()->where('asset_id', $asset->id)->firstOrFail();

        $this->expectException(QueryException::class);
        $event->update(['reason' => 'rewritten']);
    }

    public function test_correction_of_historical_projections_appends_each_domain_history(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $classification = $this->classification('3');
        $location = AssetLocation::query()->create(['tenant_id' => $fixture['tenant']->id, 'code' => 'L3', 'name' => 'Warehouse', 'location_type' => 'room', 'status' => 'active']);
        $unit = OrganizationalUnit::query()->create(['tenant_id' => $fixture['tenant']->id, 'code' => 'UNIT-3', 'name' => 'Unit Three', 'type' => 'office', 'status' => 'active']);
        $responsible = ResponsibleParty::query()->create(['tenant_id' => $fixture['tenant']->id, 'party_type' => 'organizational_unit', 'organizational_unit_id' => $unit->id, 'status' => 'active']);

        app(ManageHistoricalAssetState::class)->correct(
            $asset,
            [
                'classification_id' => $classification->id,
                'current_location_id' => $location->id,
                'current_responsible_party_id' => $responsible->id,
                'lifecycle_status' => 'active',
            ],
            'Validated projection correction',
            $fixture['user']->id,
            now()->addMinute(),
            1,
            'MEMO-02',
            'correction-projections-1',
        );

        $asset->refresh();
        $this->assertSame($classification->id, $asset->classification_id);
        $this->assertSame($location->id, $asset->current_location_id);
        $this->assertSame($responsible->id, $asset->current_responsible_party_id);
        $this->assertSame('active', $asset->lifecycle_status);
        $this->assertSame(2, AssetClassificationAssignment::query()->where('asset_id', $asset->id)->count());
        $this->assertSame(2, AssetMutation::query()->where('asset_id', $asset->id)->count());
        $this->assertSame(2, AssetResponsibilityAssignment::query()->where('asset_id', $asset->id)->count());
        $this->assertSame(2, AssetLifecycleEvent::query()->where('asset_id', $asset->id)->count());
        $this->assertSame(1, AssetClassificationAssignment::query()->where('asset_id', $asset->id)->whereNull('valid_until')->count());
        $this->assertSame(1, AssetResponsibilityAssignment::query()->where('asset_id', $asset->id)->whereNull('valid_until')->count());
    }

    #[DataProvider('crossTenantHistoryTypes')]
    public function test_postgresql_rejects_every_cross_tenant_history_reference(string $type): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $otherTenant = Tenant::factory()->active()->create();
        $base = ['tenant_id' => $otherTenant->id, 'asset_id' => $asset->id, 'created_at' => now()];
        $values = match ($type) {
            'classification' => $base + ['classification_id' => $fixture['classification']->id, 'valid_from' => now(), 'assignment_type' => 'invalid', 'assigned_by' => $fixture['user']->id],
            'mutation' => $base + ['uuid' => fake()->uuid(), 'destination_location_id' => $fixture['location']->id, 'mutation_type' => 'invalid', 'effective_at' => now(), 'status' => 'completed', 'requested_by' => $fixture['user']->id, 'updated_at' => now()],
            'responsibility' => $base + ['responsible_party_id' => $fixture['responsible_party']->id, 'valid_from' => now(), 'assigned_by' => $fixture['user']->id],
            'condition' => $base + ['uuid' => fake()->uuid(), 'new_condition' => 'good', 'effective_at' => now(), 'source_type' => 'invalid', 'actor_id' => $fixture['user']->id],
            'lifecycle' => $base + ['uuid' => fake()->uuid(), 'to_status' => 'draft', 'transition_type' => 'invalid', 'effective_at' => now(), 'actor_id' => $fixture['user']->id],
            'correction' => $base + ['uuid' => fake()->uuid(), 'correction_type' => 'invalid', 'corrected_fields' => '{}', 'before_values' => '{}', 'after_values' => '{}', 'reason' => 'invalid', 'applied_by' => $fixture['user']->id, 'applied_at' => now()],
        };
        $table = match ($type) {
            'classification' => 'asset_classification_assignments',
            'mutation' => 'asset_mutations',
            'responsibility' => 'asset_responsibility_assignments',
            'condition' => 'asset_condition_events',
            'lifecycle' => 'asset_lifecycle_events',
            'correction' => 'asset_corrections',
        };

        $this->expectException(QueryException::class);
        DB::table($table)->insert($values);
    }

    public static function crossTenantHistoryTypes(): array
    {
        return [
            'classification assignment' => ['classification'],
            'mutation' => ['mutation'],
            'responsibility assignment' => ['responsibility'],
            'condition event' => ['condition'],
            'lifecycle event' => ['lifecycle'],
            'correction' => ['correction'],
        ];
    }

    public function test_projection_failure_rolls_back_the_new_history_row(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        DB::unprepared("CREATE FUNCTION b08_force_rollback() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'forced rollback'; END; $$ LANGUAGE plpgsql");
        DB::unprepared('CREATE TRIGGER b08_force_rollback BEFORE UPDATE ON assets FOR EACH ROW EXECUTE FUNCTION b08_force_rollback()');

        try {
            app(ManageHistoricalAssetState::class)->changeCondition($asset, 'damaged', $fixture['user']->id, now(), 1, 'inspection');
            $this->fail('Projection failure was not raised.');
        } catch (QueryException) {
            $this->assertSame('good', $asset->fresh()->condition);
            $this->assertSame(1, AssetConditionEvent::query()->where('asset_id', $asset->id)->count());
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS b08_force_rollback ON assets');
            DB::unprepared('DROP FUNCTION IF EXISTS b08_force_rollback()');
        }
    }

    public function test_history_uses_locked_current_projection_instead_of_a_stale_model_snapshot(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $stale = $asset->fresh();
        $second = AssetLocation::query()->create(['tenant_id' => $fixture['tenant']->id, 'code' => 'L2', 'name' => 'Second', 'location_type' => 'room', 'status' => 'active']);
        $third = AssetLocation::query()->create(['tenant_id' => $fixture['tenant']->id, 'code' => 'L3', 'name' => 'Third', 'location_type' => 'room', 'status' => 'active']);
        $history = app(ManageHistoricalAssetState::class);

        $history->move($asset, $second->id, $fixture['user']->id, now()->addMinute(), 1, 'relocation');
        $mutation = $history->move($stale, $third->id, $fixture['user']->id, now()->addMinutes(2), 2, 'relocation');

        $this->assertSame($second->id, $mutation->origin_location_id);
        $this->assertSame($third->id, $asset->fresh()->current_location_id);
    }

    public function test_idempotency_key_cannot_replay_another_asset_operation(): void
    {
        $fixture = $this->fixture();
        $firstAsset = $this->asset($fixture);
        $secondAsset = $this->asset($fixture);
        $history = app(ManageHistoricalAssetState::class);
        $history->changeCondition($firstAsset, 'damaged', $fixture['user']->id, now(), 1, 'inspection', null, 'shared-key');

        $this->expectException(RuntimeException::class);
        $history->changeCondition($secondAsset, 'damaged', $fixture['user']->id, now(), 1, 'inspection', null, 'shared-key');
    }

    public function test_actor_without_active_tenant_membership_cannot_write_history(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $outsider = User::factory()->create();

        try {
            app(ManageHistoricalAssetState::class)->changeCondition($asset, 'damaged', $outsider->id, now(), 1, 'inspection');
            $this->fail('Actor outside the tenant was accepted.');
        } catch (AuthorizationException) {
            $this->assertSame('good', $asset->fresh()->condition);
            $this->assertSame(1, AssetConditionEvent::query()->where('asset_id', $asset->id)->count());
        }
    }

    public function test_controlled_correction_requires_a_reason(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);

        $this->expectException(\InvalidArgumentException::class);
        app(ManageHistoricalAssetState::class)->correct($asset, ['name' => 'Corrected'], '', $fixture['user']->id, now(), 1);
    }

    public function test_database_rejects_duplicate_open_and_cross_tenant_assignments(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);

        $this->expectException(QueryException::class);
        AssetClassificationAssignment::query()->create([
            'tenant_id' => $fixture['tenant']->id,
            'asset_id' => $asset->id,
            'classification_id' => $fixture['classification']->id,
            'valid_from' => now(),
            'assignment_type' => 'invalid_duplicate',
            'assigned_by' => $fixture['user']->id,
        ]);
    }

    public function test_history_action_rejects_actor_without_active_tenant_membership(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $outsider = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        app(ManageHistoricalAssetState::class)->changeCondition($asset, 'damaged', $outsider->id, now(), 1, 'inspection');
    }

    public function test_history_action_rejects_write_for_suspended_tenant(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);
        $fixture['tenant']->update(['status' => 'suspended', 'suspended_at' => now()]);

        $this->expectException(AuthorizationException::class);
        app(ManageHistoricalAssetState::class)->changeCondition($asset, 'damaged', $fixture['user']->id, now(), 1, 'inspection');
    }

    public function test_workflow_reference_cannot_be_populated_before_b12(): void
    {
        $fixture = $this->fixture();
        $asset = $this->asset($fixture);

        $this->expectException(QueryException::class);
        DB::table('asset_condition_events')->insert([
            'uuid' => fake()->uuid(),
            'tenant_id' => $fixture['tenant']->id,
            'asset_id' => $asset->id,
            'new_condition' => 'good',
            'effective_at' => now(),
            'source_type' => 'invalid',
            'actor_id' => $fixture['user']->id,
            'workflow_instance_id' => 1,
            'created_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function fixture(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);
        $classification = $this->classification('1');
        $unit = Unit::query()->firstOrCreate(['code' => 'UNIT'], ['name' => 'Unit', 'status' => 'active']);
        $location = AssetLocation::query()->create(['tenant_id' => $tenant->id, 'code' => 'L1', 'name' => 'Office', 'location_type' => 'room', 'status' => 'active']);
        $responsibleParty = ResponsibleParty::query()->create(['tenant_id' => $tenant->id, 'party_type' => 'membership', 'membership_id' => $membership->id, 'status' => 'active']);

        return compact('tenant', 'user', 'classification', 'unit', 'location', 'responsibleParty') + ['responsible_party' => $responsibleParty];
    }

    private function classification(string $code): AssetClassification
    {
        $scheme = ClassificationScheme::query()->firstOrCreate(['code' => 'S'], ['name' => 'Scheme', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->firstOrCreate(
            ['classification_scheme_id' => $scheme->id, 'version_label' => '1'],
            ['effective_from' => today(), 'status' => 'published'],
        );

        return AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => $code, 'name' => "Asset {$code}", 'level' => 1, 'status' => 'active']);
    }

    /** @param array<string, mixed> $fixture */
    private function asset(array $fixture): Asset
    {
        return Asset::query()->create([
            'tenant_id' => $fixture['tenant']->id,
            'classification_id' => $fixture['classification']->id,
            'name' => 'Synthetic Asset',
            'quantity' => 1,
            'unit_id' => $fixture['unit']->id,
            'current_location_id' => $fixture['location']->id,
            'current_responsible_party_id' => $fixture['responsible_party']->id,
            'condition' => 'good',
            'lifecycle_status' => 'draft',
            'verification_status' => 'unverified',
            'created_by' => $fixture['user']->id,
            'updated_by' => $fixture['user']->id,
        ]);
    }
}
