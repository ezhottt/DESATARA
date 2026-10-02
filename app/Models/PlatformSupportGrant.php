<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable $valid_until
 * @property CarbonImmutable|null $revoked_at
 */
class PlatformSupportGrant extends Model
{
    protected $fillable = ['tenant_id', 'platform_user_id', 'scope', 'reason', 'granted_by_user_id', 'valid_from', 'valid_until', 'revoked_at', 'revoked_by_user_id', 'audit_correlation_id'];

    protected function casts(): array
    {
        return ['scope' => 'array', 'valid_from' => 'immutable_datetime', 'valid_until' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function platformUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'platform_user_id');
    }

    public function isValid(): bool
    {
        $now = now();

        return ! $this->revoked_at && $this->valid_from->lte($now) && $this->valid_until->gte($now);
    }
}
