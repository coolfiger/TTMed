<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'users';

    protected $fillable = [
        'branch_id',
        'username',
        'email',
        'name',
        'password',
        'is_active',
        'must_change_password',
        'mfa_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'must_change_password' => 'boolean',
        'mfa_enabled'         => 'boolean',
        'last_login_at'       => 'datetime',
        'password_changed_at' => 'datetime',
        'locked_until'        => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->uuid = $user->uuid ?? (string) Str::uuid();
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->getAllPermissions()->contains('slug', $permissionSlug);
    }

    public function hasAnyPermission(array $permissionSlugs): bool
    {
        $permissions = $this->getAllPermissions()->pluck('slug');

        return collect($permissionSlugs)->intersect($permissions)->isNotEmpty();
    }

    public function getAllPermissions(): Collection
    {
        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id');
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains('slug', $roleSlug);
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
        $this->save();
    }

    public function resetLoginAttempts(): void
    {
        $this->login_attempts   = 0;
        $this->locked_until     = null;
        $this->last_login_at    = now();
        $this->last_login_ip    = request()->ip();
        $this->save();
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function lockAccount(): void
    {
        $this->locked_until = now()->addMinutes(15);
        $this->save();
    }
}
