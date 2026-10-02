<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetTransferItem extends Model
{
    protected $fillable = ['tenant_id', 'asset_transfer_id', 'asset_id', 'snapshot_payload'];

    protected function casts(): array
    {
        return ['snapshot_payload' => 'array'];
    }
}
