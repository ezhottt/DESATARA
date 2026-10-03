<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegulatoryBinding extends Model
{
    protected $fillable = ['business_rule_id', 'binding_type', 'binding_key', 'effective_from', 'effective_until', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'effective_from' => 'immutable_date', 'effective_until' => 'immutable_date'];
    }
}
