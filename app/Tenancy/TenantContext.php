<?php

namespace App\Tenancy;

use App\Models\Platform\Tenant;

/**
 * Holds the resolved tenant for the current request.
 * Registered as a singleton in AppServiceProvider.
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function resolved(): bool
    {
        return $this->tenant !== null;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function uuid(): ?string
    {
        return $this->tenant?->uuid;
    }

    public function storagePath(string $path = ''): string
    {
        $root = rtrim(config('tenancy.storage_root'), '/');
        $base = $root . '/' . $this->tenant->uuid;

        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}
