<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NursingAssessment extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'encounter_id', 'patient_id', 'nurse_id',
        'chief_complaint', 'nursing_notes',
        'allergies_confirmed', 'allergies_reviewed',
        'medications_reviewed', 'medications_noted',
        'status', 'completed_at',
    ];

    protected $casts = [
        'allergies_reviewed'  => 'boolean',
        'medications_reviewed' => 'boolean',
        'completed_at'        => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(NursingProcedure::class, 'encounter_id', 'encounter_id');
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }
}
