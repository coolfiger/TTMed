<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueEvent extends Model
{
    protected $connection = 'tenant';

    public $timestamps = false;

    protected $fillable = [
        'queue_entry_id',
        'event_type',
        'from_status',
        'to_status',
        'performed_by',
        'notes',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function queueEntry(): BelongsTo
    {
        return $this->belongsTo(QueueEntry::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
