<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRow extends Model
{
    protected $fillable = ['tenant_id', 'import_job_id', 'row_number', 'raw_payload', 'normalized_payload', 'status', 'created_resource_type', 'created_resource_id'];

    protected $casts = ['raw_payload' => 'array', 'normalized_payload' => 'array'];
}
