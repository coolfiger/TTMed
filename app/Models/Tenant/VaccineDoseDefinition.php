<?php

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccineDoseDefinition extends Model
{
    protected $connection = 'tenant';
    public $timestamps = false;

    protected $fillable = [
        'vaccine_catalog_id', 'dose_number', 'dose_label',
        'reference', 'reference_weeks', 'min_weeks', 'max_weeks', 'notes',
    ];

    protected $casts = [
        'dose_number'      => 'integer',
        'reference_weeks'  => 'integer',
        'min_weeks'        => 'integer',
        'max_weeks'        => 'integer',
    ];

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(VaccineCatalog::class, 'vaccine_catalog_id');
    }

    /**
     * Calculate the recommended due date.
     * For 'dob' reference: based on patient's date of birth.
     * For 'prev_dose' reference: based on the date the previous dose was administered.
     */
    public function dueDateFrom(?Carbon $dob, ?Carbon $prevDoseDate = null): ?Carbon
    {
        if ($this->reference === 'dob') {
            return $dob?->copy()->addWeeks($this->reference_weeks);
        }

        return $prevDoseDate?->copy()->addWeeks($this->reference_weeks);
    }
}
