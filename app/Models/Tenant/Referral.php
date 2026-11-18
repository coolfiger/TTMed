<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'encounter_id', 'patient_id', 'referred_by',
        'referred_to_name', 'referred_to_specialty', 'referred_to_facility',
        'urgency', 'reason', 'clinical_summary', 'instructions_to_patient',
        'status', 'sent_at', 'appointment_date', 'feedback_notes',
    ];

    protected $casts = [
        'sent_at'          => 'datetime',
        'appointment_date' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function getUrgencyColorAttribute(): string
    {
        return match ($this->urgency) {
            'emergency' => 'red',
            'urgent'    => 'orange',
            default     => 'gray',
        };
    }
}
