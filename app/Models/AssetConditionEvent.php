<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssetConditionEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['uuid', 'tenant_id', 'asset_id', 'previous_condition', 'new_condition', 'effective_at', 'reason', 'source_type', 'actor_id', 'workflow_instance_id', 'idempotency_key', 'created_at'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['effective_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }
}
