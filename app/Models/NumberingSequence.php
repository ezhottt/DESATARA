<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class NumberingSequence extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'sequence_type',
        'period_key',
        'prefix',
        'suffix',
        'last_number',
        'lock_version',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
            'lock_version' => 'integer',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
