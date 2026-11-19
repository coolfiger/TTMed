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

    private function ensureConnected(string $method): void
    {
        $tenant = $this->context->get();

        \Illuminate\Support\Facades\Log::error('TenantUserProvider::ensureConnected', [
            'method'        => $method,
            'tenant_code'   => $tenant?->code,
            'tenant_db'     => $tenant?->database_name,
            'config_db'     => \Illuminate\Support\Facades\Config::get('database.connections.tenant.database'),
            'pid'           => getmypid(),
            'url'           => request()->fullUrl(),
            'trace'         => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8))
                ->skip(1)->map(fn($f) => ($f['class'] ?? '') . '::' . ($f['function'] ?? ''))->implode(' → '),
        ]);

        if ($tenant) {
            $this->dbManager->connect($tenant);
        }
    }

    public function retrieveById($identifier)
    {
        $this->ensureConnected('retrieveById');
        $result = parent::retrieveById($identifier);
        \Illuminate\Support\Facades\Log::error('TenantUserProvider::retrieveById result', ['found' => ! is_null($result), 'id' => $identifier]);
        return $result;
    }

    public function retrieveByToken($identifier, $token)
    {
        $this->ensureConnected('retrieveByToken');
        return parent::retrieveByToken($identifier, $token);
    }

    public function updateRememberToken(\Illuminate\Contracts\Auth\Authenticatable $user, $token)
    {
        $this->ensureConnected('updateRememberToken');
        parent::updateRememberToken($user, $token);
    }

    public function retrieveByCredentials(array $credentials)
    {
        $this->ensureConnected('retrieveByCredentials');
        return parent::retrieveByCredentials($credentials);
    }

    public function validateCredentials(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials)
    {
        $this->ensureConnected('validateCredentials');
        return parent::validateCredentials($user, $credentials);
    }
}
