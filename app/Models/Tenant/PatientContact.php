<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientContact extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id',
        'name',
        'relationship',
        'phone',
        'email',
        'address',
        'is_emergency_contact',
        'is_next_of_kin',
    ];

    protected $casts = [
        'is_emergency_contact' => 'boolean',
        'is_next_of_kin'       => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
