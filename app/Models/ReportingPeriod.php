<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** @property CarbonImmutable $start_date */
/** @property CarbonImmutable $end_date */
class ReportingPeriod extends Model
{
    protected $fillable = ['tenant_id', 'year', 'period_type', 'period_number', 'start_date', 'end_date', 'deadline', 'status'];

    protected function casts(): array
    {
        return ['start_date' => 'immutable_date', 'end_date' => 'immutable_date', 'deadline' => 'immutable_date'];
    }
}
