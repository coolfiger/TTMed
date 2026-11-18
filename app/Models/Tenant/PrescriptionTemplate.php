<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionTemplate extends Model
{
    protected $connection = 'tenant';

    protected $fillable = ['doctor_id', 'name', 'items'];

    protected $casts = [
        'items' => 'array',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
