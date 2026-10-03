<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssetMutation extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'asset_id', 'origin_location_id', 'destination_location_id', 'mutation_type', 'reason', 'effective_at', 'status', 'requested_by', 'executed_by', 'workflow_instance_id', 'idempotency_key'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['effective_at' => 'immutable_datetime'];
    }
}
