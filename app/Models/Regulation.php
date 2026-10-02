<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regulation extends Model
{
    protected $fillable = ['code', 'title', 'jurisdiction_level', 'issuing_authority'];
}
