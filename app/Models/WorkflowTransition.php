<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class WorkflowTransition extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'workflow_instance_id', 'from_state', 'to_state', 'action', 'actor_membership_id', 'authority_snapshot_id', 'reason', 'idempotency_key', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Workflow transitions are append-only.'));
        static::deleting(fn () => throw new LogicException('Workflow transitions are append-only.'));
    }
}
