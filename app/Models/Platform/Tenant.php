<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $connection = 'platform';

    protected $table = 'tenants';

    protected $fillable = [
        'code',
        'name',
        'business_name',
        'database_name',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'custom_domain',
        'status',
        'storage_path',
        'contact_name',
        'contact_email',
        'contact_phone',
        'backup_frequency',
        'backup_hour',
        'backup_day',
        'backup_retain',
        'backup_last_run_at',
    ];

    protected $casts = [
        'backup_last_run_at' => 'datetime',
    ];

    protected $hidden = [
        'db_password',
        'db_username',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            $tenant->uuid = $tenant->uuid ?? (string) Str::uuid();
            $tenant->code = strtolower($tenant->code);
        });
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PlatformAuditLog::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function getLoginUrlAttribute(): string
    {
        $domain = config('tenancy.domain');
        $scheme = app()->isProduction() ? 'https' : 'http';

        return "{$scheme}://{$this->code}.{$domain}/login";
    }
}
