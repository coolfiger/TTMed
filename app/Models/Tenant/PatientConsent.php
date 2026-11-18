<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsent extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id',
        'consent_type',
        'is_given',
        'given_by',
        'relationship_to_patient',
        'consent_date',
        'expiry_date',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'is_given'     => 'boolean',
        'consent_date' => 'date',
        'expiry_date'  => 'date',
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
