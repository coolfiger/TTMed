<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'branch_id', 'cashier_id', 'status',
        'opened_at', 'closed_at', 'approved_at', 'approved_by',
        'opening_float', 'closing_float', 'expected_cash', 'actual_cash', 'variance',
        'notes',
    ];

    protected $casts = [
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
        'approved_at'     => 'datetime',
        'opening_float'   => 'integer',
        'closing_float'   => 'integer',
        'expected_cash'   => 'integer',
        'actual_cash'     => 'integer',
        'variance'        => 'integer',
    ];

    public function isOpen(): bool     { return $this->status === 'open'; }
    public function isClosed(): bool   { return $this->status === 'closed'; }
    public function isApproved(): bool { return $this->status === 'approved'; }

    public function getVarianceFormattedAttribute(): string
    {
        $sign = $this->variance < 0 ? '-' : '';
        return $sign . '$' . number_format(abs($this->variance) / 100, 2);
    }

    public function cashier(): BelongsTo    { return $this->belongsTo(User::class, 'cashier_id'); }
    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'cashier_shift_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
