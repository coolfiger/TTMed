<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'encounter_id', 'patient_id', 'doctor_id', 'branch_id',
        'appointment_id', 'target_date', 'follow_up_days',
        'reason', 'instructions', 'status', 'created_by',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function encounter(): BelongsTo    { return $this->belongsTo(Encounter::class); }
    public function patient(): BelongsTo      { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo       { return $this->belongsTo(Doctor::class); }
    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function appointment(): BelongsTo  { return $this->belongsTo(Appointment::class); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isBooked(): bool     { return $this->status === 'booked'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'yellow',
            'booked'    => 'blue',
            'completed' => 'green',
            'cancelled' => 'gray',
            default     => 'gray',
        };
    }
}
