<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'price',
        'cost',
        'tax_rate',
        'taxable',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'price'      => 'integer',
        'cost'       => 'integer',
        'tax_rate'   => 'decimal:2',
        'taxable'    => 'boolean',
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function getPriceFormattedAttribute(): string
    {
        return number_format($this->price / 100, 2);
    }

    public function getPriceWithTaxAttribute(): int
    {
        if (!$this->taxable || $this->tax_rate <= 0) {
            return $this->price;
        }

        return (int) round($this->price * (1 + $this->tax_rate / 100));
    }
}
