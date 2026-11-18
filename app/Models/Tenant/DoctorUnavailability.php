<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorUnavailability extends Model
{
    protected $connection = 'tenant';
    protected $table     = 'doctor_unavailability';

    protected $fillable = [
        'doctor_id', 'branch_id', 'unavailable_date',
        'from_time', 'to_time', 'reason',
    ];

    protected $casts = [
        'unavailable_date' => 'date',
    ];

    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    public function isWholeDay(): bool
    {
        return $this->from_time === null;
    }
}
