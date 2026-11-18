<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'invoice_id', 'insurance_provider_id', 'claim_reference', 'status',
        'claimed_amount', 'approved_amount', 'paid_amount',
        'submitted_date', 'approved_date', 'paid_date', 'notes',
    ];

    protected $casts = [
        'claimed_amount'  => 'integer',
        'approved_amount' => 'integer',
        'paid_amount'     => 'integer',
        'submitted_date'  => 'date',
        'approved_date'   => 'date',
        'paid_date'       => 'date',
    ];

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isSubmitted(): bool  { return $this->status === 'submitted'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isPaid(): bool       { return $this->status === 'paid'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'yellow',
            'submitted' => 'blue',
            'approved'  => 'teal',
            'partial'   => 'orange',
            'paid'      => 'green',
            'rejected'  => 'red',
            default     => 'gray',
        };
    }

    public function invoice(): BelongsTo           { return $this->belongsTo(Invoice::class); }
    public function insuranceProvider(): BelongsTo { return $this->belongsTo(InsuranceProvider::class); }
}
