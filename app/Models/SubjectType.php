<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectType extends Model
{
    protected $fillable = ['code', 'name', 'model_key', 'status'];
}
