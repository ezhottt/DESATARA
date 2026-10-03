<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ReportSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'asset_report_id', 'snapshot_payload', 'artifact_document_id', 'artifact_checksum', 'snapshot_checksum', 'created_at'];

    protected function casts(): array
    {
        return ['snapshot_payload' => 'array', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Report snapshots are immutable.'));
        static::deleting(fn () => throw new LogicException('Report snapshots are immutable.'));
    }
}
