<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'asset_id', 'maintenance_type', 'planned_at', 'started_at', 'completed_at', 'vendor', 'planned_cost', 'actual_cost', 'funding_source_id', 'condition_before', 'condition_after', 'status', 'notes', 'created_by', 'updated_by'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['planned_at' => 'date', 'started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'planned_cost' => 'decimal:2', 'actual_cost' => 'decimal:2'];
    }
}
