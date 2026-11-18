<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTestCategory extends Model
{
    protected $connection = 'tenant';

    protected $fillable = ['name', 'code', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function tests(): HasMany
    {
        return $this->hasMany(LabTest::class, 'category_id');
    }
}
