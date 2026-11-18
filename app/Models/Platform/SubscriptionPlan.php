<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $connection = 'platform';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'features',
        'user_limit',
        'storage_limit_gb',
        'monthly_price',
        'annual_price',
        'is_active',
    ];

    protected $casts = [
        'features'        => 'array',
        'is_active'       => 'boolean',
        'monthly_price'   => 'decimal:2',
        'annual_price'    => 'decimal:2',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}
