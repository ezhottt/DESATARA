<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDiscrepancy extends Model
{
    protected $fillable = ['tenant_id', 'inventory_session_id', 'inventory_item_id', 'asset_id', 'discrepancy_type', 'description', 'status', 'proposed_resolution', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'immutable_datetime'];
    }
}
