<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'invoice_number', 'patient_id', 'branch_id', 'encounter_id',
        'insurance_provider_id', 'status',
        'subtotal', 'tax_total', 'discount', 'total',
        'amount_paid', 'amount_due', 'insurance_portion', 'patient_portion',
        'notes', 'created_by', 'finalized_by', 'finalized_at',
        'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'finalized_at'   => 'datetime',
        'cancelled_at'   => 'datetime',
        'subtotal'       => 'integer',
        'tax_total'      => 'integer',
        'discount'       => 'integer',
        'total'          => 'integer',
        'amount_paid'    => 'integer',
        'amount_due'     => 'integer',
        'insurance_portion' => 'integer',
        'patient_portion'   => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------------------------

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isFinalized(): bool  { return $this->status === 'finalized'; }
    public function isPaid(): bool       { return $this->status === 'paid'; }
    public function isPartiallyPaid(): bool { return $this->status === 'partially_paid'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }
    public function isVoid(): bool       { return $this->status === 'void'; }

    public function isOpen(): bool
    {
        return in_array($this->status, ['draft', 'finalized', 'partially_paid']);
    }

    public function isPayable(): bool
    {
        return in_array($this->status, ['finalized', 'partially_paid']);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'          => 'Draft',
            'finalized'      => 'Finalized',
            'paid'           => 'Paid',
            'partially_paid' => 'Partially Paid',
            'cancelled'      => 'Cancelled',
            'void'           => 'Void',
            default          => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'          => 'gray',
            'finalized'      => 'blue',
            'paid'           => 'green',
            'partially_paid' => 'yellow',
            'cancelled'      => 'red',
            'void'           => 'red',
            default          => 'gray',
        };
    }

    // -------------------------------------------------------------------------
    // Formatted money helpers (TTD dollars)
    // -------------------------------------------------------------------------

    public function getFormattedTotalAttribute(): string
    {
        return 'TTD $' . number_format($this->total / 100, 2);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'draft'          => 'bg-gray-100 text-gray-700',
            'finalized'      => 'bg-blue-100 text-blue-700',
            'paid'           => 'bg-green-100 text-green-700',
            'partially_paid' => 'bg-yellow-100 text-yellow-700',
            'cancelled'      => 'bg-red-100 text-red-700',
            'void'           => 'bg-red-100 text-red-700',
            default          => 'bg-gray-100 text-gray-700',
        };
    }

    public function getTotalFormattedAttribute(): string
    {
        return 'TTD $' . number_format($this->total / 100, 2);
    }

    public function getAmountDueFormattedAttribute(): string
    {
        return '$' . number_format($this->amount_due / 100, 2);
    }

    public function getAmountPaidFormattedAttribute(): string
    {
        return '$' . number_format($this->amount_paid / 100, 2);
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function patient(): BelongsTo     { return $this->belongsTo(Patient::class); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }
    public function encounter(): BelongsTo   { return $this->belongsTo(Encounter::class); }
    public function createdBy(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
    public function finalizedBy(): BelongsTo { return $this->belongsTo(User::class, 'finalized_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function insuranceClaim(): HasOne
    {
        return $this->hasOne(InsuranceClaim::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['draft', 'finalized', 'partially_paid']);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
