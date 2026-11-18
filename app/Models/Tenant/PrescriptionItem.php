<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'prescription_id', 'medication_id',
        'medication_name', 'strength', 'form', 'route',
        'dose', 'frequency', 'duration', 'duration_unit',
        'quantity', 'instructions', 'refills_allowed', 'sort_order',
    ];

    protected $casts = [
        'refills_allowed' => 'integer',
        'sort_order'      => 'integer',
        'duration'        => 'integer',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function getDurationLabelAttribute(): string
    {
        if (!$this->duration) {
            return '';
        }
        $unit = $this->duration === 1
            ? rtrim($this->duration_unit, 's')
            : $this->duration_unit;
        return "{$this->duration} {$unit}";
    }
}
