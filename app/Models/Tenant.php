<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = ['uuid', 'village_code', 'name', 'province', 'regency', 'district', 'address', 'timezone', 'locale', 'status', 'activated_at', 'suspended_at', 'archived_at'];

    protected function casts(): array
    {
        return ['activated_at' => 'immutable_datetime', 'suspended_at' => 'immutable_datetime', 'archived_at' => 'immutable_datetime'];
    }

    /** @return HasMany<TenantMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /** @return HasOne<TenantSetting, $this> */
    public function setting(): HasOne
    {
        return $this->hasOne(TenantSetting::class);
    }

    /** @return HasMany<PlatformSupportGrant, $this> */
    public function supportGrants(): HasMany
    {
        return $this->hasMany(PlatformSupportGrant::class);
    }

    public function isOperational(): bool
    {
        return $this->status === 'active';
    }
}
