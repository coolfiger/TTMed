<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'doctor_id', 'branch_id', 'day_of_week', 'is_working_day',
        'start_time', 'end_time', 'slot_duration_minutes',
        'max_patients_per_slot', 'max_patients_per_day',
    ];

    protected $casts = [
        'is_working_day' => 'boolean',
    ];

    protected static array $dayNames = [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];

    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    public function getDayNameAttribute(): string
    {
        return self::$dayNames[$this->day_of_week] ?? 'Unknown';
    }
}
