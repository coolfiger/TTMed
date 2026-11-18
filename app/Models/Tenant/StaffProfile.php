<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'user_id',
        'branch_id',
        'title',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'mobile',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'profile_photo_path',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active'     => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(($this->title ? $this->title . ' ' : '') . $this->first_name . ' ' . $this->last_name);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }
}
