<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ApprovalAction extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'approval_request_id', 'approval_step_id', 'actor_membership_id', 'authority_snapshot_id', 'action', 'notes', 'idempotency_key', 'acted_at'];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Approval actions are append-only.'));
        static::deleting(fn () => throw new LogicException('Approval actions are append-only.'));
    }
}
