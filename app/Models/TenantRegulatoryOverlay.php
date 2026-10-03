<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantRegulatoryOverlay extends Model
{
    protected $fillable = ['tenant_id', 'business_rule_id', 'mode', 'configuration', 'status'];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }
}
