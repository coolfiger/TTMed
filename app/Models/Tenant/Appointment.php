<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'appointment_number', 'patient_id', 'doctor_id', 'branch_id', 'encounter_id',
        'appointment_date', 'appointment_time', 'duration_minutes', 'appointment_type',
        'status', 'reason', 'notes', 'booked_by', 'cancelled_by', 'cancellation_reason',
        'cancelled_at', 'arrived_at', 'reminder_sent', 'reminder_sent_at',
        'rescheduled_to', 'rescheduled_from',
    ];

    protected $casts = [
        'appointment_date'   => 'date',
        'cancelled_at'       => 'datetime',
        'arrived_at'         => 'datetime',
        'reminder_sent_at'   => 'datetime',
        'reminder_sent'      => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo    { return $this->belongsTo(Doctor::class); }
    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function bookedBy(): BelongsTo  { return $this->belongsTo(User::class, 'booked_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function rescheduledTo(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_to');
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_from');
    }

    // -------------------------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------------------------

    public function isScheduled(): bool    { return $this->status === 'scheduled'; }
    public function isConfirmed(): bool    { return $this->status === 'confirmed'; }
    public function isArrived(): bool      { return $this->status === 'arrived'; }
    public function isCompleted(): bool    { return $this->status === 'completed'; }
    public function isCancelled(): bool    { return $this->status === 'cancelled'; }
    public function isNoShow(): bool       { return $this->status === 'no_show'; }
    public function isRescheduled(): bool  { return $this->status === 'rescheduled'; }

    public function isActive(): bool
    {
        return in_array($this->status, ['scheduled', 'confirmed', 'arrived', 'in_progress']);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled'   => 'blue',
            'confirmed'   => 'indigo',
            'arrived'     => 'yellow',
            'in_progress' => 'orange',
            'completed'   => 'green',
            'cancelled'   => 'red',
            'no_show'     => 'gray',
            'rescheduled' => 'purple',
            default       => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'scheduled'   => 'Scheduled',
            'confirmed'   => 'Confirmed',
            'arrived'     => 'Arrived',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'cancelled'   => 'Cancelled',
            'no_show'     => 'No Show',
            'rescheduled' => 'Rescheduled',
            default       => ucfirst($this->status),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->appointment_type) {
            'scheduled'  => 'Scheduled',
            'walk_in'    => 'Walk-in',
            'follow_up'  => 'Follow-up',
            'urgent'     => 'Urgent',
            default      => ucfirst($this->appointment_type),
        };
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'confirmed', 'arrived', 'in_progress']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
                     ->whereIn('status', ['scheduled', 'confirmed']);
    }
}
