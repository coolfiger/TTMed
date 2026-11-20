<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class InsuranceProvider extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'code',
        'contact_name',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function formTemplates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InsuranceFormTemplate::class);
    }
}
