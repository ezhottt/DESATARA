<?php

namespace App\Models;

/**
 * @property int $asset_id
 */

use Illuminate\Database\Eloquent\Model;

/** @property int $asset_id */
class AssetDisposalItem extends Model
{
    protected $fillable = ['tenant_id', 'asset_disposal_id', 'asset_id', 'snapshot_payload'];

    protected function casts(): array
    {
        return ['snapshot_payload' => 'array'];
    }
}
