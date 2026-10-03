<?php

namespace App\Models;

/**
 * @property string $status
 * @property int $lock_version
 */

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** @property string $status @property int $lock_version */
class AssetUsageDetermination extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'period_year', 'decision_number', 'decision_date', 'status', 'authority_snapshot_id', 'workflow_instance_id', 'finalized_at', 'lock_version', 'created_by'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['decision_date' => 'immutable_date', 'finalized_at' => 'immutable_datetime'];
    }
}
