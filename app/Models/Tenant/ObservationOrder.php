<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObservationOrder extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'encounter_id',
        'patient_id',
        'branch_id',
        'ordered_by',
        'reason',
        'treatment_notes',
        'room_name',
        'status',
        'started_at',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_ACTIVE    = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

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

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ObservationEntry::class)->orderBy('occurred_at');
    }

    public function latestEntry(): HasMany
    {
        return $this->hasMany(ObservationEntry::class)->latest('occurred_at')->limit(1);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function durationMinutes(): ?int
    {
        $end = $this->completed_at ?? now();
        return (int) $this->started_at->diffInMinutes($end);
    }
}
