<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'invoice_id', 'service_id', 'description',
        'quantity', 'unit_price', 'tax_rate', 'tax_amount', 'discount', 'line_total',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'integer',
        'tax_rate'   => 'decimal:2',
        'tax_amount' => 'integer',
        'discount'   => 'integer',
        'line_total' => 'integer',
    ];

    public function invoice(): BelongsTo  { return $this->belongsTo(Invoice::class); }
    public function service(): BelongsTo  { return $this->belongsTo(Service::class); }

    public function getLineTotalFormattedAttribute(): string
    {
        return '$' . number_format($this->line_total / 100, 2);
    }
}
