<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservationEntry extends Model
{
    protected $connection = 'tenant';

    public $timestamps = false;

    protected $fillable = [
        'observation_order_id',
        'encounter_id',
        'patient_id',
        'recorded_by',
        'bp_systolic',
        'bp_diastolic',
        'pulse',
        'temp_celsius',
        'spo2',
        'notes',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at'  => 'datetime',
        'temp_celsius' => 'decimal:1',
    ];

    public function observationOrder(): BelongsTo
    {
        return $this->belongsTo(ObservationOrder::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function bpFormatted(): string
    {
        if ($this->bp_systolic && $this->bp_diastolic) {
            return "{$this->bp_systolic}/{$this->bp_diastolic}";
        }
        return '—';
    }
}
