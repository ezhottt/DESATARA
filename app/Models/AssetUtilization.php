<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssetUtilization extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'asset_id', 'utilization_type', 'counterparty', 'start_at', 'end_at', 'amount', 'status', 'workflow_instance_id', 'lock_version'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['start_at' => 'immutable_datetime', 'end_at' => 'immutable_datetime', 'amount' => 'decimal:2'];
    }
}
