<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'tenant_id', 'import_type', 'status', 'strategy', 'mapping_config', 'total_rows', 'valid_rows', 'invalid_rows', 'imported_rows', 'requested_by'];

    protected $casts = ['mapping_config' => 'array'];

    /** @return HasMany<ImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    /** @return HasMany<ImportError, $this> */
    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
