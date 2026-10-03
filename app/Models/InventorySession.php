<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed> $scope_definition
 */
class InventorySession extends Model
{
    protected $fillable = ['uuid', 'tenant_id', 'name', 'period_label', 'scope_definition', 'reference_at', 'status', 'started_at', 'reviewed_at', 'finalized_at', 'created_by', 'lock_version'];

    protected function casts(): array
    {
        return ['scope_definition' => 'array', 'reference_at' => 'immutable_datetime', 'started_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime', 'finalized_at' => 'immutable_datetime'];
    }

    /** @return HasMany<InventoryItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /** @return HasMany<InventoryDiscovery, $this> */
    public function discoveries(): HasMany
    {
        return $this->hasMany(InventoryDiscovery::class);
    }

    /** @return HasMany<InventoryDiscrepancy, $this> */
    public function discrepancies(): HasMany
    {
        return $this->hasMany(InventoryDiscrepancy::class);
    }
}
