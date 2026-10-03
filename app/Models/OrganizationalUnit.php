<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationalUnit extends Model
{
    protected $fillable = ['tenant_id', 'parent_id', 'code', 'name', 'type', 'status', 'valid_from', 'valid_until'];
}
