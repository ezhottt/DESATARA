<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponsibleParty extends Model
{
    protected $fillable = ['tenant_id', 'party_type', 'membership_id', 'organizational_unit_id', 'status', 'valid_from', 'valid_until'];
}
