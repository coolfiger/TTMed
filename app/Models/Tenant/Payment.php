<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'payment_reference', 'invoice_id', 'patient_id', 'branch_id',
        'cashier_shift_id', 'payment_method', 'amount', 'reference_number',
        'notes', 'collected_by', 'voided', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'amount'    => 'integer',
        'voided'    => 'boolean',
        'voided_at' => 'datetime',
    ];

    public function isVoided(): bool { return $this->voided; }

    public function getAmountFormattedAttribute(): string
    {
        return '$' . number_format($this->amount / 100, 2);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash'      => 'Cash',
            'card'      => 'Card',
            'linx'      => 'LINX',
            'cheque'    => 'Cheque',
            'insurance' => 'Insurance',
            'waiver'    => 'Waiver',
            default     => ucfirst($this->payment_method),
        };
    }

    public function invoice(): BelongsTo       { return $this->belongsTo(Invoice::class); }
    public function patient(): BelongsTo       { return $this->belongsTo(Patient::class); }
    public function branch(): BelongsTo        { return $this->belongsTo(Branch::class); }
    public function collectedBy(): BelongsTo   { return $this->belongsTo(User::class, 'collected_by'); }
    public function voidedBy(): BelongsTo      { return $this->belongsTo(User::class, 'voided_by'); }
    public function cashierShift(): BelongsTo  { return $this->belongsTo(CashierShift::class); }
}
