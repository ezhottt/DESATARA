<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSetting extends Model
{
    protected $fillable = ['tenant_id', 'branding', 'numbering_config', 'reporting_config', 'operational_config'];

    protected function casts(): array
    {
        return ['branding' => 'array', 'numbering_config' => 'array', 'reporting_config' => 'array', 'operational_config' => 'array'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
