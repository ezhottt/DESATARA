<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ApprovalRequest extends Model
{
    protected $fillable = ['uuid', 'tenant_id', 'workflow_instance_id', 'subject_type_id', 'subject_id', 'status', 'requested_by', 'submitted_at', 'completed_at', 'lock_version'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    /** @return HasMany<ApprovalStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class);
    }

    /** @return BelongsTo<WorkflowInstance, $this> */
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $request): void {
            if ($request->getOriginal('status') === 'finalized' && $request->isDirty('status')) {
                throw new LogicException('Finalized approval requests are immutable.');
            }
        });
    }
}
