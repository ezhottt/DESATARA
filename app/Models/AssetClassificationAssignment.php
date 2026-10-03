<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetClassificationAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'asset_id', 'classification_id', 'valid_from', 'valid_until', 'assignment_type', 'reason', 'assigned_by', 'workflow_instance_id', 'created_at'];

    protected function casts(): array
    {
        return ['valid_from' => 'immutable_datetime', 'valid_until' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }
}
