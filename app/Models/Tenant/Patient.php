<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'patient_number',
        'title',
        'first_name',
        'last_name',
        'middle_name',
        'preferred_name',
        'gender',
        'date_of_birth',
        'national_id',
        'passport_number',
        'phone_primary',
        'phone_secondary',
        'email',
        'address',
        'city',
        'country',
        'occupation',
        'employer',
        'marital_status',
        'blood_type',
        'insurance_provider_id',
        'insurance_policy_number',
        'insurance_group_number',
        'registered_by',
        'primary_doctor_id',
        'branch_id',
        'is_active',
        'is_deceased',
        'deceased_date',
        'notes',
    ];

    protected $casts = [
        'date_of_birth'  => 'date',
        'deceased_date'  => 'date',
        'is_active'      => 'boolean',
        'is_deceased'    => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->title,
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function primaryDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'primary_doctor_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PatientContact::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class)->where('is_active', true)->orderBy('allergen');
    }

    public function allAllergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class)->orderBy('allergen');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PatientCondition::class)->orderByRaw("FIELD(status,'active','chronic','in_remission','resolved')");
    }

    public function medications(): HasMany
    {
        return $this->hasMany(PatientMedication::class)->where('is_current', true)->orderBy('medication_name');
    }

    public function allMedications(): HasMany
    {
        return $this->hasMany(PatientMedication::class)->orderBy('medication_name');
    }

    public function medicalHistory(): HasOne
    {
        return $this->hasOne(PatientMedicalHistory::class);
    }

    public function familyHistories(): HasMany
    {
        return $this->hasMany(PatientFamilyHistory::class)->orderBy('relationship');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class)->orderBy('consent_date', 'desc');
    }

    public function physicalFile(): HasOne
    {
        return $this->hasOne(PatientPhysicalFile::class);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('patient_number', 'like', "%{$term}%")
              ->orWhere('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('phone_primary', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('national_id', 'like', "%{$term}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
