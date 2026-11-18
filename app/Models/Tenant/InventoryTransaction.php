<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'inventory_item_id', 'branch_id', 'transaction_type',
        'quantity', 'quantity_before', 'quantity_after',
        'reference', 'notes', 'performed_by',
    ];

    protected $casts = [
        'quantity'        => 'integer',
        'quantity_before' => 'integer',
        'quantity_after'  => 'integer',
    ];

    public function item(): BelongsTo        { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function performedBy(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            'receive'   => 'Received',
            'dispense'  => 'Dispensed',
            'adjust'    => 'Adjusted',
            'return'    => 'Returned',
            'write_off' => 'Written Off',
            default     => ucfirst($this->transaction_type),
        };
    }
}
