<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabOrderItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'lab_order_id', 'lab_test_id', 'lab_panel_id',
        'test_name', 'test_code', 'loinc_code',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class);
    }
}
