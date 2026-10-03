<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDiscovery extends Model
{
    protected $fillable = ['uuid', 'tenant_id', 'inventory_session_id', 'temporary_label', 'description', 'observed_location_id', 'observed_condition', 'resolution_status', 'resolved_asset_id', 'resolved_by', 'resolved_at', 'created_by'];

    protected function casts(): array
    {
        return ['resolved_at' => 'immutable_datetime'];
    }
}
