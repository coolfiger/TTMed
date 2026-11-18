<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientPhysicalFile extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id',
        'file_number',
        'location_cabinet',
        'location_shelf',
        'location_drawer',
        'current_location_note',
    ];

    public function getLocationSummaryAttribute(): string
    {
        $parts = array_filter([
            $this->location_cabinet ? "Cabinet {$this->location_cabinet}" : null,
            $this->location_shelf   ? "Shelf {$this->location_shelf}"     : null,
            $this->location_drawer  ? "Drawer {$this->location_drawer}"   : null,
        ]);

        return $parts ? implode(', ', $parts) : ($this->current_location_note ?? 'Unknown');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PatientFileMovement::class)->orderBy('moved_at', 'desc');
    }
}
