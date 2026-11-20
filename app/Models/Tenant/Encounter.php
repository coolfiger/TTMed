<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Encounter extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id',
        'branch_id',
        'doctor_id',
        'queue_entry_id',
        'visit_type',
        'status',
        'encounter_date',
        'checked_in_at',
        'seen_by_nurse_at',
        'seen_by_doctor_at',
        'completed_at',
        'checked_in_by',
        'chief_complaint',
        'notes',
    ];

    protected $casts = [
        'encounter_date'     => 'date',
        'checked_in_at'      => 'datetime',
        'seen_by_nurse_at'   => 'datetime',
        'seen_by_doctor_at'  => 'datetime',
        'completed_at'       => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function queueEntry(): HasOne
    {
        return $this->hasOne(QueueEntry::class);
    }

    public function consultationNote(): HasOne
    {
        return $this->hasOne(ConsultationNote::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class)->orderBy('diagnosis_type');
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class)->orderBy('recorded_at');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class)->orderByDesc('created_at');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class)->orderByDesc('created_at');
    }
}
