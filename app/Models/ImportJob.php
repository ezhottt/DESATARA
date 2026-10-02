<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'import_type', 'status', 'strategy', 'mapping_config', 'total_rows', 'valid_rows', 'invalid_rows', 'imported_rows', 'requested_by'];

    protected $casts = ['mapping_config' => 'array'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
