<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetLocation extends Model
{
    protected $fillable = ['tenant_id', 'parent_id', 'code', 'name', 'location_type', 'status'];
}
