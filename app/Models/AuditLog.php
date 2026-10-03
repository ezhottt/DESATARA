<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'actor_id', 'action', 'subject_type', 'subject_id', 'before_state', 'after_state', 'correlation_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['before_state' => 'array', 'after_state' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
