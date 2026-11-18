<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAvailability extends Model
{
    protected $connection = 'tenant';

    protected $table = 'doctor_availability';

    protected $fillable = [
        'doctor_id',
        'branch_id',
        'status',
        'status_note',
        'updated_by',
    ];

    const STATUS_AVAILABLE      = 'available';
    const STATUS_BUSY           = 'busy';
    const STATUS_ON_BREAK       = 'on_break';
    const STATUS_OFF_DUTY       = 'off_duty';
    const STATUS_NOT_ACCEPTING  = 'not_accepting';

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available'     => 'Available',
            'busy'          => 'Busy',
            'on_break'      => 'On Break',
            'off_duty'      => 'Off Duty',
            'not_accepting' => 'Not Accepting',
            default         => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available'     => 'green',
            'busy'          => 'yellow',
            'on_break'      => 'orange',
            'off_duty'      => 'gray',
            'not_accepting' => 'red',
            default         => 'gray',
        };
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
