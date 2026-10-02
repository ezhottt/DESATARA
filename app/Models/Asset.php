<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasUuids;

    protected $attributes = ['quantity' => 1, 'lifecycle_status' => 'draft', 'verification_status' => 'unverified', 'lock_version' => 1];

    protected $fillable = ['uuid', 'tenant_id', 'classification_id', 'asset_code', 'register_number', 'name', 'description', 'acquisition_date', 'acquisition_year', 'acquisition_origin', 'funding_source_id', 'quantity', 'unit_id', 'unit_price', 'acquisition_value', 'current_location_id', 'current_responsible_party_id', 'condition', 'lifecycle_status', 'verification_status', 'lock_version', 'created_by', 'updated_by'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return HasMany<AssetAcquisition, $this> */
    public function acquisitions(): HasMany
    {
        return $this->hasMany(AssetAcquisition::class);
    }
}
