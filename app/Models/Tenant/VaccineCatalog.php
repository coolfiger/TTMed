<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VaccineCatalog extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vaccine_catalog';

    protected $fillable = [
        'name', 'short_name', 'description', 'disease_target',
        'type', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function doseDefinitions(): HasMany
    {
        return $this->hasMany(VaccineDoseDefinition::class)->orderBy('dose_number');
    }

    public function patientVaccinations(): HasMany
    {
        return $this->hasMany(PatientVaccination::class);
    }

    public function isSingleDose(): bool
    {
        return $this->type === 'single_dose';
    }
}
