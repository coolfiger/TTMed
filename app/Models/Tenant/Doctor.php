<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'staff_profile_id',
        'registration_number',
        'specialty',
        'qualifications',
        'signature_path',
        'stamp_path',
        'prescription_logo_path',
        'prescription_header',
        'prescription_footer',
        'consultation_fee_code',
        'available_for_appointments',
    ];

    protected $casts = [
        'available_for_appointments' => 'boolean',
    ];

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function operatingHours(): HasMany
    {
        return $this->hasMany(DoctorOperatingHour::class)->orderBy('day_of_week');
    }

    public function getNameAttribute(): string
    {
        return $this->staffProfile?->full_name ?? '';
    }
}
