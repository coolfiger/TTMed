<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'email',
        'is_main',
        'is_active',
    ];

    protected $casts = [
        'is_main'   => 'boolean',
        'is_active' => 'boolean',
    ];

    public function operatingHours(): HasMany
    {
        return $this->hasMany(BranchOperatingHour::class)->orderBy('day_of_week');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }
}
