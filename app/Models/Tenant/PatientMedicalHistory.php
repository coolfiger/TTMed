<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalHistory extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id',
        'surgical_history',
        'family_history',
        'social_history',
        'obstetric_history',
        'immunisation_history',
        'other_notes',
        'updated_by',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
