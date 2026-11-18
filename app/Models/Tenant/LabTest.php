<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LabTest extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'category_id', 'name', 'code', 'loinc_code',
        'specimen_type', 'container', 'unit',
        'reference_range_low', 'reference_range_high', 'reference_range_text',
        'is_active', 'is_system',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LabTestCategory::class, 'category_id');
    }

    public function panels(): BelongsToMany
    {
        return $this->belongsToMany(LabPanel::class, 'lab_panel_tests', 'test_id', 'panel_id');
    }
}
