<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    protected $fillable = ['tenant_id', 'inventory_session_id', 'asset_id', 'asset_uuid_snapshot', 'asset_code_snapshot', 'register_number_snapshot', 'asset_name_snapshot', 'classification_id_snapshot', 'classification_code_snapshot', 'classification_name_snapshot', 'expected_location_id', 'expected_location_snapshot', 'observed_location_id', 'observed_location_snapshot', 'expected_condition', 'observed_condition', 'lifecycle_status_snapshot', 'verification_result', 'verified_by', 'verified_at', 'snapshot_payload'];

    protected function casts(): array
    {
        return ['expected_location_snapshot' => 'array', 'observed_location_snapshot' => 'array', 'snapshot_payload' => 'array', 'verified_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<InventorySession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(InventorySession::class, 'inventory_session_id');
    }
}
