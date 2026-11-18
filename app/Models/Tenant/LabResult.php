<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'lab_order_id', 'lab_order_item_id', 'patient_id', 'lab_test_id',
        'test_name', 'test_code', 'loinc_code',
        'value', 'value_unit', 'reference_range', 'flag', 'is_critical',
        'interpretation_notes', 'performing_lab', 'result_datetime',
        'source', 'raw_message',
    ];

    protected $casts = [
        'is_critical'      => 'boolean',
        'result_datetime'  => 'datetime',
    ];

    public function getFlagLabelAttribute(): string
    {
        return match ($this->flag) {
            'normal'        => 'Normal',
            'low'           => 'Low',
            'high'          => 'High',
            'critical_low'  => 'Critical Low',
            'critical_high' => 'Critical High',
            'abnormal'      => 'Abnormal',
            default         => ucfirst($this->flag),
        };
    }

    public function getFlagColorAttribute(): string
    {
        return match ($this->flag) {
            'normal'                     => 'green',
            'low', 'high', 'abnormal'    => 'yellow',
            'critical_low', 'critical_high' => 'red',
            default                      => 'gray',
        };
    }

    public function isAbnormal(): bool
    {
        return $this->flag !== 'normal';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(LabOrderItem::class, 'lab_order_item_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }
}
