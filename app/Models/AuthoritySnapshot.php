<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuthoritySnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'user_id', 'membership_id', 'tenant_official_id', 'authority_type', 'official_type', 'position_name', 'appointment_number', 'valid_from', 'valid_until', 'snapshot_payload', 'captured_at'];

    protected function casts(): array
    {
        return ['snapshot_payload' => 'array', 'valid_from' => 'date', 'valid_until' => 'date', 'captured_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Authority snapshots are immutable.'));
        static::deleting(fn () => throw new LogicException('Authority snapshots are immutable.'));
    }
}
