<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssetSafeguard extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'asset_id', 'safeguard_category', 'finding', 'action', 'verification_status', 'verified_by', 'verified_at', 'created_by'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['verified_at' => 'immutable_datetime'];
    }
}
