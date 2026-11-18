<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'encounter_id', 'patient_id', 'icd10_code_id',
        'icd10_code', 'diagnosis_text',
        'diagnosis_type', 'certainty', 'notes',
        'diagnosed_by',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function icd10(): BelongsTo
    {
        return $this->belongsTo(Icd10Code::class, 'icd10_code_id');
    }

    public function diagnosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diagnosed_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->diagnosis_type) {
            'primary'      => 'Primary',
            'secondary'    => 'Secondary',
            'differential' => 'Differential',
            default        => ucfirst($this->diagnosis_type),
        };
    }

    public function getCertaintyLabelAttribute(): string
    {
        return match ($this->certainty) {
            'confirmed'   => 'Confirmed',
            'probable'    => 'Probable',
            'suspected'   => 'Suspected',
            'ruled_out'   => 'Ruled Out',
            default       => ucfirst($this->certainty),
        };
    }

    public function getCertaintyColorAttribute(): string
    {
        return match ($this->certainty) {
            'confirmed' => 'green',
            'probable'  => 'blue',
            'suspected' => 'yellow',
            'ruled_out' => 'gray',
            default     => 'gray',
        };
    }
}
