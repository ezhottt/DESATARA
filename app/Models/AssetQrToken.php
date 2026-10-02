<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetQrToken extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'asset_id', 'token_hash', 'status', 'issued_at', 'revoked_at', 'rotated_from_id', 'created_by', 'created_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['issued_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }
}
