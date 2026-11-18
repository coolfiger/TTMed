<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'category_id', 'name', 'code', 'description', 'unit',
        'quantity_on_hand', 'reorder_level', 'reorder_quantity',
        'unit_cost', 'is_active', 'track_expiry', 'expiry_date', 'supplier',
    ];

    protected $casts = [
        'quantity_on_hand'  => 'integer',
        'reorder_level'     => 'integer',
        'reorder_quantity'  => 'integer',
        'unit_cost'         => 'integer',
        'is_active'         => 'boolean',
        'track_expiry'      => 'boolean',
        'expiry_date'       => 'date',
    ];

    public function isLowStock(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity_on_hand === 0;
    }

    public function getUnitCostFormattedAttribute(): string
    {
        return '$' . number_format($this->unit_cost / 100, 2);
    }

    public function getStockValueAttribute(): int
    {
        return $this->quantity_on_hand * $this->unit_cost;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'reorder_level');
    }
}
