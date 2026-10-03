<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetClassification extends Model
{
    protected $fillable = ['classification_version_id', 'parent_id', 'code', 'name', 'level', 'status'];
}
