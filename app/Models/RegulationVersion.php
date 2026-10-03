<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegulationVersion extends Model
{
    protected $fillable = ['regulation_id', 'version_label', 'effective_from', 'effective_until', 'status', 'source_reference', 'source_checksum'];

    protected function casts(): array
    {
        return ['effective_from' => 'immutable_date', 'effective_until' => 'immutable_date'];
    }
}
