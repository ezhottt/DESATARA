<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TenantMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $joined_at
 * @property CarbonImmutable|null $valid_from
 * @property CarbonImmutable|null $valid_until
 */
class TenantMembership extends Model
{
    /** @use HasFactory<TenantMembershipFactory> */
    use HasFactory;

    protected $fillable = ['tenant_id', 'user_id', 'status', 'joined_at', 'valid_from', 'valid_until'];

    protected function casts(): array
    {
        return ['joined_at' => 'immutable_datetime', 'valid_from' => 'immutable_datetime', 'valid_until' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCurrentlyActive(): bool
    {
        $now = now();

        return $this->status === 'active' && (! $this->valid_from || $this->valid_from->lte($now)) && (! $this->valid_until || $this->valid_until->gte($now));
    }
}
