<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasUuids;

    protected $attributes = ['quantity' => 1, 'lifecycle_status' => 'draft', 'verification_status' => 'unverified', 'lock_version' => 1];

    public function getNupAttribute(): ?string
    {
        return $this->register_number;
    }

    public function setNupAttribute(?string $value): void
    {
        $this->attributes['register_number'] = $value;
    }

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

    /** @return BelongsTo<AssetClassification, $this> */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(AssetClassification::class, 'classification_id');
    }

    /** @return HasMany<AssetPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(AssetPhoto::class);
    }

    /** @return HasMany<AssetClassificationAssignment, $this> */
    public function classificationAssignments(): HasMany
    {
        return $this->hasMany(AssetClassificationAssignment::class);
    }

    /** @return HasMany<AssetMutation, $this> */
    public function mutations(): HasMany
    {
        return $this->hasMany(AssetMutation::class);
    }

    /** @return HasMany<AssetResponsibilityAssignment, $this> */
    public function responsibilityAssignments(): HasMany
    {
        return $this->hasMany(AssetResponsibilityAssignment::class);
    }

    /** @return HasMany<AssetConditionEvent, $this> */
    public function conditionEvents(): HasMany
    {
        return $this->hasMany(AssetConditionEvent::class);
    }

    /** @return HasMany<AssetLifecycleEvent, $this> */
    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(AssetLifecycleEvent::class);
    }

    /** @return HasMany<AssetCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(AssetCorrection::class);
    }
}
