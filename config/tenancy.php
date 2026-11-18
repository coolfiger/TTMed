<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Parent Domain
    |--------------------------------------------------------------------------
    | The root domain for the platform. Tenant subdomains are resolved
    | as {tenant_code}.{domain}.
    */
    'domain' => env('APP_DOMAIN', 'ttmed.app'),

    /*
    |--------------------------------------------------------------------------
    | Platform Subdomain
    |--------------------------------------------------------------------------
    | The subdomain reserved for the platform administration portal.
    */
    'platform_subdomain' => env('PLATFORM_SUBDOMAIN', 'platform'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution
    |--------------------------------------------------------------------------
    | 'subdomain' - resolve tenant from {code}.ttmed.app
    | 'session'   - resolve tenant from session (fallback for localhost dev)
    */
    'resolution' => env('TENANT_RESOLUTION', 'subdomain'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Encryption Key
    |--------------------------------------------------------------------------
    | Used to encrypt tenant database passwords at rest in the platform DB.
    | Must be exactly 32 characters.
    */
    'encryption_key' => env('TENANT_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Storage Root
    |--------------------------------------------------------------------------
    | Absolute path to the root directory for tenant file storage.
    | Each tenant gets a subdirectory: {storage_root}/{tenant_uuid}/
    */
    'storage_root' => env('TENANT_STORAGE_PATH', storage_path('tenants')),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Prefix
    |--------------------------------------------------------------------------
    | All tenant database names will be: {prefix}{tenant_code}
    */
    'db_prefix' => env('TENANT_DB_PREFIX', 'ttmed_'),

    /*
    |--------------------------------------------------------------------------
    | Provisioner Database Connection
    |--------------------------------------------------------------------------
    | A privileged MySQL user that can CREATE databases and users.
    | Only used during tenant provisioning.
    */
    'provisioner' => [
        'host'     => env('DB_PROVISIONER_HOST', env('DB_HOST', '127.0.0.1')),
        'port'     => env('DB_PROVISIONER_PORT', env('DB_PORT', '3306')),
        'username' => env('DB_PROVISIONER_USERNAME', 'ttmed_provisioner'),
        'password' => env('DB_PROVISIONER_PASSWORD', ''),
    ],

];
