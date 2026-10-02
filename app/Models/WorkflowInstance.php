<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class WorkflowInstance extends Model
{
    protected $fillable = ['uuid', 'tenant_id', 'workflow_version_id', 'subject_type_id', 'subject_id', 'current_state', 'started_by', 'started_at', 'completed_at', 'cancelled_at', 'lock_version'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    /** @return BelongsTo<WorkflowVersion, $this> */
    public function workflowVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $instance): void {
            if ($instance->getOriginal('current_state') === 'FINALIZED') {
                throw new LogicException('Finalized workflow instances are immutable.');
            }
        });
    }
}
