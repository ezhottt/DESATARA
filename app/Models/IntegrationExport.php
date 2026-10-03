<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IntegrationExport extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'target_system', 'format_version', 'status', 'requested_by', 'generated_document_id', 'checksum', 'metadata', 'generated_at', 'exported_at', 'reconciled_at'];

    protected $casts = ['metadata' => 'array', 'generated_at' => 'datetime', 'exported_at' => 'datetime', 'reconciled_at' => 'datetime'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
