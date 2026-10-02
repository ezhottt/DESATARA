<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAcquisition extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'asset_id', 'acquisition_type', 'acquisition_date', 'source', 'funding_source_id', 'quantity', 'unit_value', 'total_value', 'counterparty', 'reference_number', 'notes', 'created_by', 'created_at'];
}
