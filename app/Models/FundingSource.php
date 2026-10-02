<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundingSource extends Model
{
    protected $fillable = ['tenant_id', 'code', 'name', 'status'];
}
