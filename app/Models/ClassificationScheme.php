<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassificationScheme extends Model
{
    protected $fillable = ['code', 'name', 'scope', 'status'];
}
