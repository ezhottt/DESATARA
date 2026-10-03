<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssetCorrection extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['uuid', 'tenant_id', 'asset_id', 'correction_type', 'corrected_fields', 'before_values', 'after_values', 'reason', 'reference', 'applied_by', 'applied_at', 'workflow_instance_id', 'idempotency_key', 'created_at'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return [
            'corrected_fields' => 'array',
            'before_values' => 'array',
            'after_values' => 'array',
            'applied_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
