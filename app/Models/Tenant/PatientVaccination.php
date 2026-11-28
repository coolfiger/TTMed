<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientVaccination extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id', 'vaccine_catalog_id', 'vaccine_dose_definition_id',
        'dose_number', 'encounter_id', 'branch_id', 'administered_by',
        'administered_on', 'lot_number', 'site', 'route', 'notes',
    ];

    protected $casts = [
        'administered_on' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(VaccineCatalog::class, 'vaccine_catalog_id');
    }

    public function doseDefinition(): BelongsTo
    {
        return $this->belongsTo(VaccineDoseDefinition::class, 'vaccine_dose_definition_id');
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
