<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientIntakeToken extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'type', 'patient_id', 'token', 'pin', 'expires_at', 'used_at', 'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    // For registration tokens: patient_id is null until patient submits the form.
    // After submission, patient_id is set to the new patient's ID and used_at is set.
    public function isValidRegistration(): bool
    {
        return $this->type === 'registration'
            && $this->patient_id === null
            && $this->used_at === null
            && $this->expires_at->isFuture();
    }

    public function isRegistrationComplete(): bool
    {
        return $this->type === 'registration' && $this->patient_id !== null;
    }
}
