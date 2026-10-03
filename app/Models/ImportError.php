<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    protected $fillable = ['tenant_id', 'import_job_id', 'import_row_id', 'field_name', 'error_code', 'message'];
}
