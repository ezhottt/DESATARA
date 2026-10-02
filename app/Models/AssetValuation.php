<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssetValuation extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'asset_id', 'purpose', 'valuation_date', 'amount', 'valuer_name', 'valuer_reference', 'authority_snapshot_id', 'workflow_instance_id', 'status', 'created_by'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['valuation_date' => 'date', 'amount' => 'decimal:2'];
    }
}
