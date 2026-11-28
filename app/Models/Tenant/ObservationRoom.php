<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObservationRoom extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'branch_id',
        'name',
        'notes',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const STATUS_VACANT   = 'vacant';
    const STATUS_OCCUPIED = 'occupied';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function observationOrders(): HasMany
    {
        return $this->hasMany(ObservationOrder::class, 'room_id');
    }

    public function currentOrder(): HasMany
    {
        return $this->hasMany(ObservationOrder::class, 'room_id')
                    ->where('status', ObservationOrder::STATUS_ACTIVE);
    }

    public function isVacant(): bool
    {
        return $this->status === self::STATUS_VACANT;
    }
}
