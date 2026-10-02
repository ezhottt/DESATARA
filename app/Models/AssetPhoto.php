<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetPhoto extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'asset_id', 'document_id', 'category', 'captured_at', 'uploaded_by', 'notes', 'created_at'];
}
