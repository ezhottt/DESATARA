<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class AssetReport extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'reporting_period_id', 'report_template_version_id', 'status', 'revision_number', 'parent_report_id', 'generated_by', 'reviewed_by', 'finalized_by', 'workflow_instance_id', 'lock_version', 'generated_at', 'reviewed_at', 'finalized_at'];

    protected function casts(): array
    {
        return ['generated_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime', 'finalized_at' => 'immutable_datetime'];
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return HasOne<ReportSnapshot, $this> */
    public function snapshot(): HasOne
    {
        return $this->hasOne(ReportSnapshot::class);
    }

    /** @return BelongsTo<AssetReport, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_report_id');
    }

    protected static function booted(): void
    {
        static::updating(function (self $report): void {
            if ($report->getOriginal('status') === 'finalized') {
                throw new LogicException('Finalized reports are immutable.');
            }
        });
        static::deleting(function (self $report): void {
            if ($report->status === 'finalized') {
                throw new LogicException('Finalized reports are immutable.');
            }
        });
    }
}
