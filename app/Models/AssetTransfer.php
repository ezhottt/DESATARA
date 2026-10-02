<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetTransfer extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'transfer_type', 'request_date', 'status', 'workflow_instance_id', 'authority_snapshot_id', 'formal_decision_document_id', 'executed_at', 'lock_version', 'idempotency_key', 'created_by'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['request_date' => 'date', 'executed_at' => 'immutable_datetime'];
    }

    /** @return HasMany<AssetTransferItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AssetTransferItem::class);
    }
}
