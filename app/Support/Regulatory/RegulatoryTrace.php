<?php

namespace App\Support\Regulatory;

use App\Models\BusinessRule;
use App\Models\Regulation;
use App\Models\RegulationVersion;
use RuntimeException;

final class RegulatoryTrace
{
    /** @return array<string, string> */
    public function forRule(string $code): array
    {
        $rule = BusinessRule::query()->where('code', $code)->firstOrFail();
        $provision = $rule->provisions()->first() ?? throw new RuntimeException('Rule has no provision trace.');
        $version = RegulationVersion::query()->findOrFail($provision->regulation_version_id);
        $regulation = Regulation::query()->findOrFail($version->regulation_id);
        $binding = $rule->bindings()->first() ?? throw new RuntimeException('Rule has no system contract binding.');

        return [
            'regulation_code' => (string) $regulation->getAttribute('code'),
            'regulation_version' => (string) $version->getAttribute('version_label'),
            'provision_number' => (string) $provision->getAttribute('provision_number'),
            'business_rule' => (string) $rule->getAttribute('code'),
            'system_contract' => (string) $binding->getAttribute('binding_key'),
            'binding_type' => (string) $binding->getAttribute('binding_type'),
        ];
    }
}
