<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LabPanel extends Model
{
    protected $connection = 'tenant';

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(LabTest::class, 'lab_panel_tests', 'panel_id', 'test_id')
                    ->orderBy('sort_order');
    }
}
