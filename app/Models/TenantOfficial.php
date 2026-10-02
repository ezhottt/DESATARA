<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** @property CarbonImmutable|null  */
class TenantOfficial extends Model
{
    protected $fillable = ['tenant_id', 'membership_id', 'official_type', 'position_name', 'authority_code', 'authority_scope', 'appointment_number', 'appointment_date', 'valid_from', 'valid_until', 'status', 'evidence_document_id'];

    protected function casts(): array
    {
        return ['authority_scope' => 'array', 'appointment_date' => 'date', 'valid_from' => 'date', 'valid_until' => 'date'];
    }
}
