<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class PlatformUser extends Authenticatable
{
    use Notifiable;
    protected $connection = 'platform';

    protected $table = 'platform_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'mfa_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'mfa_enabled' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PlatformUser $user) {
            $user->uuid = $user->uuid ?? (string) Str::uuid();
        });
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PlatformAuditLog::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
