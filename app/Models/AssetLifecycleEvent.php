<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssetLifecycleEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['uuid', 'tenant_id', 'asset_id', 'from_status', 'to_status', 'transition_type', 'effective_at', 'reason', 'actor_id', 'workflow_instance_id', 'idempotency_key', 'created_at'];

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
