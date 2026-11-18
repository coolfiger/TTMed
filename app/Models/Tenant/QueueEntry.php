<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueEntry extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'branch_id',
        'doctor_id',
        'queue_number',
        'queue_date',
        'visit_type',
        'status',
        'is_priority',
        'priority_reason',
        'checked_in_by',
        'called_at',
    ];

    protected $casts = [
        'queue_date'  => 'date',
        'is_priority' => 'boolean',
        'called_at'   => 'datetime',
    ];

    const STATUS_WAITING        = 'waiting';
    const STATUS_WITH_NURSE     = 'with_nurse';
    const STATUS_AWAITING_DOCTOR = 'awaiting_doctor';
    const STATUS_WITH_DOCTOR    = 'with_doctor';
    const STATUS_COMPLETED      = 'completed';
    const STATUS_CANCELLED      = 'cancelled';
    const STATUS_NO_SHOW        = 'no_show';

    const VALID_TRANSITIONS = [
        'waiting'         => ['with_nurse', 'awaiting_doctor', 'cancelled', 'no_show'],
        'with_nurse'      => ['awaiting_doctor', 'cancelled'],
        'awaiting_doctor' => ['with_doctor', 'cancelled', 'no_show'],
        'with_doctor'     => ['completed', 'cancelled'],
        'completed'       => [],
        'cancelled'       => [],
        'no_show'         => [],
    ];

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_TRANSITIONS[$this->status] ?? []);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

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

    public function events(): HasMany
    {
        return $this->hasMany(QueueEvent::class)->orderBy('occurred_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'waiting'         => 'Waiting',
            'with_nurse'      => 'With Nurse',
            'awaiting_doctor' => 'Awaiting Doctor',
            'with_doctor'     => 'With Doctor',
            'completed'       => 'Completed',
            'cancelled'       => 'Cancelled',
            'no_show'         => 'No Show',
            default           => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'waiting'         => 'yellow',
            'with_nurse'      => 'blue',
            'awaiting_doctor' => 'orange',
            'with_doctor'     => 'green',
            'completed'       => 'gray',
            'cancelled'       => 'red',
            'no_show'         => 'red',
            default           => 'gray',
        };
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled', 'no_show']);
    }

    public function scopeForBranchToday($query, int $branchId)
    {
        return $query->where('branch_id', $branchId)
                     ->where('queue_date', today());
    }
}
