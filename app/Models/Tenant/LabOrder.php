<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrder extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'order_number', 'patient_id', 'encounter_id', 'ordered_by', 'branch_id',
        'status', 'order_type', 'clinical_notes',
        'lis_placer_order_number', 'lis_filler_order_number', 'integration_type',
        'ordered_at', 'collected_at', 'collected_by',
        'sent_to_lab_at', 'results_received_at', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'ordered_at'          => 'datetime',
        'collected_at'        => 'datetime',
        'sent_to_lab_at'      => 'datetime',
        'results_received_at' => 'datetime',
        'reviewed_at'         => 'datetime',
    ];

    const STATUS_PENDING           = 'pending';
    const STATUS_SPECIMEN_COLLECTED = 'specimen_collected';
    const STATUS_SENT_TO_LAB       = 'sent_to_lab';
    const STATUS_PROCESSING        = 'processing';
    const STATUS_PARTIAL_RESULTS   = 'partial_results';
    const STATUS_RESULTS_RECEIVED  = 'results_received';
    const STATUS_RESULTS_REVIEWED  = 'results_reviewed';
    const STATUS_CANCELLED         = 'cancelled';

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'            => 'Pending',
            'specimen_collected' => 'Specimen Collected',
            'sent_to_lab'        => 'Sent to Lab',
            'processing'         => 'Processing',
            'partial_results'    => 'Partial Results',
            'results_received'   => 'Results Received',
            'results_reviewed'   => 'Reviewed',
            'cancelled'          => 'Cancelled',
            default              => ucfirst($this->status),
        };
    }

    public function hasResults(): bool
    {
        return in_array($this->status, [
            self::STATUS_PARTIAL_RESULTS,
            self::STATUS_RESULTS_RECEIVED,
            self::STATUS_RESULTS_REVIEWED,
        ]);
    }

    public function hasCriticalResults(): bool
    {
        return $this->results()->where('is_critical', true)->exists();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LabResultAttachment::class);
    }
}
