<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessRule extends Model
{
    protected $fillable = ['code', 'name', 'description', 'effective_from', 'effective_until', 'status', 'implementation_status', 'is_mandatory', 'jurisdiction_level'];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean', 'effective_from' => 'immutable_date', 'effective_until' => 'immutable_date'];
    }

    /** @return BelongsToMany<RegulationProvision,$this> */
    public function provisions(): BelongsToMany
    {
        return $this->belongsToMany(RegulationProvision::class, 'business_rule_provisions');
    }

    /** @return HasMany<RegulatoryBinding,$this> */
    public function bindings(): HasMany
    {
        return $this->hasMany(RegulatoryBinding::class);
    }
}
