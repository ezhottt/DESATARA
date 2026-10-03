<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLink extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'document_id', 'subject_type_id', 'subject_id', 'purpose', 'created_by', 'created_at'];
}
