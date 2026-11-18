<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    protected $connection = 'tenant';

    protected $fillable = ['name', 'code', 'is_system', 'is_active', 'sort_order'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];
}
