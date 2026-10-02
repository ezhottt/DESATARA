<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExternalApproval extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['uuid', 'tenant_id', 'workflow_instance_id', 'subject_type_id', 'subject_id', 'authority_organization', 'decision_type', 'document_id', 'decision_number', 'decision_date', 'status', 'recorded_by', 'created_at'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return ['decision_date' => 'immutable_date', 'created_at' => 'immutable_datetime'];
    }
}
