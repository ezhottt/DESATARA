<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegulationProvision extends Model
{
    protected $fillable = ['regulation_version_id', 'provision_type', 'provision_number', 'heading', 'content_reference'];
}
