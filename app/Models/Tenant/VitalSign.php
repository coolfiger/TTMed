<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id', 'encounter_id', 'recorded_by',
        'systolic_bp', 'diastolic_bp', 'pulse_rate', 'pulse_rhythm',
        'respiratory_rate', 'oxygen_saturation',
        'temperature', 'temperature_site',
        'weight_kg', 'height_cm', 'bmi',
        'blood_glucose', 'blood_glucose_timing',
        'pain_score', 'notes', 'recorded_at',
    ];

    protected $casts = [
        'recorded_at'       => 'datetime',
        'weight_kg'         => 'float',
        'height_cm'         => 'float',
        'bmi'               => 'float',
        'temperature'       => 'float',
        'blood_glucose'     => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (VitalSign $vs) {
            if ($vs->weight_kg > 0 && $vs->height_cm > 0) {
                $heightM = $vs->height_cm / 100;
                $vs->bmi = round($vs->weight_kg / ($heightM * $heightM), 1);
            }
        });
    }

    public function getBpDisplayAttribute(): ?string
    {
        if ($this->systolic_bp && $this->diastolic_bp) {
            return "{$this->systolic_bp}/{$this->diastolic_bp} mmHg";
        }
        return null;
    }

    public function getBmiCategoryAttribute(): ?string
    {
        if (!$this->bmi) {
            return null;
        }

        return match (true) {
            $this->bmi < 18.5 => 'Underweight',
            $this->bmi < 25.0 => 'Normal',
            $this->bmi < 30.0 => 'Overweight',
            default           => 'Obese',
        };
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
