<?php

namespace Tests\Feature\Regulatory;

use App\Models\BusinessRule;
use App\Models\Regulation;
use App\Models\RegulationProvision;
use App\Models\RegulationVersion;
use App\Models\Tenant;
use App\Support\Regulatory\RegulatoryTrace;
use App\Support\Regulatory\TenantOverlayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RegulatoryTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_rule_traces_regulation_provision_rule_and_system_contract(): void
    {
        $reg = Regulation::query()->create(['code' => 'TEST-NATIONAL', 'title' => 'Synthetic National Rule', 'jurisdiction_level' => 'national']);
        $version = RegulationVersion::query()->create(['regulation_id' => $reg->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published', 'source_reference' => 'synthetic:test']);
        $provision = RegulationProvision::query()->create(['regulation_version_id' => $version->id, 'provision_type' => 'article', 'provision_number' => '1', 'heading' => 'Synthetic provision']);
        $rule = BusinessRule::query()->create(['code' => 'BR-TEST-001', 'name' => 'Synthetic mandatory rule', 'status' => 'active', 'implementation_status' => 'implemented', 'is_mandatory' => true, 'jurisdiction_level' => 'national']);
        $rule->provisions()->attach($provision);
        $rule->bindings()->create(['binding_type' => 'workflow', 'binding_key' => 'assets.synthetic.guard', 'metadata' => []]);
        $trace = app(RegulatoryTrace::class)->forRule('BR-TEST-001');
        $this->assertSame('TEST-NATIONAL', $trace['regulation_code']);
        $this->assertSame('1', $trace['provision_number']);
        $this->assertSame('assets.synthetic.guard', $trace['system_contract']);
    }

    public function test_tenant_overlay_cannot_disable_or_relax_mandatory_national_rule(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $rule = BusinessRule::query()->create(['code' => 'BR-MANDATORY', 'name' => 'Mandatory', 'status' => 'active', 'implementation_status' => 'implemented', 'is_mandatory' => true, 'jurisdiction_level' => 'national']);
        $manager = app(TenantOverlayManager::class);
        foreach (['disable', 'relax'] as $mode) {
            try {
                $manager->apply($tenant, $rule, $mode, []);
                $this->fail('Expected rejection.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
        $overlay = $manager->apply($tenant, $rule, 'additional_constraint', ['note' => 'stricter local control']);
        $this->assertSame('additional_constraint', $overlay->mode);
    }
}
