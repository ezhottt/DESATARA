<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassificationVersion extends Model
{
    protected $fillable = ['classification_scheme_id', 'version_label', 'effective_from', 'effective_until', 'status', 'regulation_version_id'];
}
