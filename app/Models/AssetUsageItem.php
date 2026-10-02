<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetUsageItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'usage_determination_id', 'asset_id', 'responsible_party_id', 'usage_purpose', 'snapshot_payload', 'created_at'];

    protected function casts(): array
    {
        return ['snapshot_payload' => 'array', 'created_at' => 'immutable_datetime'];
    }
}
