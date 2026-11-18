<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedication extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id',
        'medication_name',
        'dosage',
        'frequency',
        'route',
        'start_date',
        'end_date',
        'is_current',
        'prescribed_by',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_current' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
