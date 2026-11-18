<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalCertificate extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'certificate_number', 'encounter_id', 'patient_id', 'doctor_id', 'branch_id',
        'certificate_type', 'from_date', 'to_date', 'diagnosis_text',
        'notes', 'instructions', 'pdf_path', 'issued_by', 'issued_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
        'issued_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function isIssued(): bool
    {
        return $this->issued_at !== null;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->certificate_type) {
            'sick_leave'     => 'Sick Leave Certificate',
            'fit_for_work'   => 'Fit for Work Certificate',
            'fitness'        => 'Medical Fitness Certificate',
            'medical_report' => 'Medical Report',
            default          => 'Medical Certificate',
        };
    }

    public function getDurationDaysAttribute(): int
    {
        if (!$this->to_date) {
            return 1;
        }
        return $this->from_date->diffInDays($this->to_date) + 1;
    }
}
