<?php

namespace App\Support\Regulatory;

use App\Models\BusinessRule;
use App\Models\Tenant;
use App\Models\TenantRegulatoryOverlay;
use InvalidArgumentException;

final class TenantOverlayManager
{
    /** @param array<string, mixed> $configuration */
    public function apply(Tenant $tenant, BusinessRule $rule, string $mode, array $configuration): TenantRegulatoryOverlay
    {
        if (! in_array($mode, ['additional_constraint', 'local_reference'], true)) {
            throw new InvalidArgumentException('Tenant overlay cannot disable or relax regulatory rules.');
        }

        return TenantRegulatoryOverlay::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'business_rule_id' => $rule->id, 'mode' => $mode],
            ['configuration' => $configuration, 'status' => 'active'],
        );
    }
}
