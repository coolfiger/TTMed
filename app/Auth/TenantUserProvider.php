<?php

namespace App\Auth;

use App\Tenancy\TenantContext;
use App\Tenancy\TenantDatabaseManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Custom user provider that re-establishes the tenant DB connection before
 * every user retrieval. This guards against race conditions and middleware
 * ordering edge cases where the tenant connection config is not yet applied
 * when the auth guard runs its queries.
 */
class TenantUserProvider extends EloquentUserProvider
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantDatabaseManager $dbManager,
        Hasher $hasher,
        string $model,
    ) {
        parent::__construct($hasher, $model);
    }

    private function ensureConnected(): void
    {
        $tenant = $this->context->get();

        \Illuminate\Support\Facades\Log::error('TenantUserProvider::ensureConnected', [
            'tenant_code'     => $tenant?->code,
            'tenant_db_user'  => $tenant?->db_username,
            'tenant_db_name'  => $tenant?->database_name,
            'config_db_now'   => \Illuminate\Support\Facades\Config::get('database.connections.tenant.database'),
            'config_user_now' => \Illuminate\Support\Facades\Config::get('database.connections.tenant.username'),
        ]);

        if ($tenant) {
            $this->dbManager->connect($tenant);
        }
    }

    public function retrieveById($identifier)
    {
        $this->ensureConnected();
        return parent::retrieveById($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        $this->ensureConnected();
        return parent::retrieveByToken($identifier, $token);
    }

    public function updateRememberToken(\Illuminate\Contracts\Auth\Authenticatable $user, $token)
    {
        $this->ensureConnected();
        parent::updateRememberToken($user, $token);
    }

    public function retrieveByCredentials(array $credentials)
    {
        $this->ensureConnected();
        return parent::retrieveByCredentials($credentials);
    }

    public function validateCredentials(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials)
    {
        $this->ensureConnected();
        return parent::validateCredentials($user, $credentials);
    }
}
