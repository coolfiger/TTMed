<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCondition extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id',
        'condition_name',
        'icd10_code',
        'diagnosed_date',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'diagnosed_date' => 'date',
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
