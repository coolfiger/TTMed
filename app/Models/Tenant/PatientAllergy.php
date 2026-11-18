<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAllergy extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id',
        'allergen',
        'reaction',
        'severity',
        'is_active',
        'recorded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            'mild'             => 'Mild',
            'moderate'         => 'Moderate',
            'severe'           => 'Severe',
            'life_threatening' => 'Life-threatening',
            default            => '—',
        };
    }
}
