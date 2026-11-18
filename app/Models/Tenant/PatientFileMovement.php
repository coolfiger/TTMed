<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFileMovement extends Model
{
    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'patient_physical_file_id',
        'from_location',
        'to_location',
        'reason',
        'moved_by',
        'moved_at',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    public function physicalFile(): BelongsTo
    {
        return $this->belongsTo(PatientPhysicalFile::class, 'patient_physical_file_id');
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
