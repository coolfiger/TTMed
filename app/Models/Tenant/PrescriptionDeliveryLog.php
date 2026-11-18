<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionDeliveryLog extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'prescription_id', 'delivery_type', 'recipient_email', 'status',
        'smtp2go_message_id', 'error_message', 'dispatched_at', 'delivered_at',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'delivered' => 'green',
            'sent'      => 'blue',
            'pending'   => 'yellow',
            'failed',
            'bounced'   => 'red',
            default     => 'gray',
        };
    }
}
