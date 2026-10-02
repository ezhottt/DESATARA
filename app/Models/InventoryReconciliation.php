<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReconciliation extends Model
{
    public $timestamps = false;

    protected $fillable = ['uuid', 'tenant_id', 'inventory_discrepancy_id', 'resolution_type', 'resolution_subject_type_id', 'resolution_subject_id', 'decision_notes', 'authority_snapshot_id', 'workflow_instance_id', 'reconciled_by', 'reconciled_at', 'idempotency_key', 'created_at'];

    protected function casts(): array
    {
        return ['reconciled_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }
}
