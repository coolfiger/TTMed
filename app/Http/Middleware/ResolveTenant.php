<?php

namespace App\Http\Middleware;

use App\Exceptions\TenantInactiveException;
use App\Exceptions\TenantNotFoundException;
use App\Models\Platform\Tenant;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private TenantContext $context,
        private TenantDatabaseManager $dbManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host     = $request->getHost();
        $domain   = config('tenancy.domain');
        $platform = config('tenancy.platform_subdomain') . '.' . $domain;

        \Illuminate\Support\Facades\Log::info('ResolveTenant::handle', [
            'host'     => $host,
            'domain'   => $domain,
            'platform' => $platform,
            'url'      => $request->fullUrl(),
            'skipping' => ($host === $platform || $host === $domain),
        ]);

        // Skip entirely for the platform subdomain or bare domain — not a tenant request
        if ($host === $platform || $host === $domain) {
            return $next($request);
        }

        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            throw new TenantNotFoundException();
        }

        if (! in_array($tenant->status, ['active', 'trial'])) {
            throw new TenantInactiveException($tenant);
        }

        $this->context->set($tenant);
        $this->dbManager->connect($tenant);

        \Illuminate\Support\Facades\Log::info('ResolveTenant::handle context set', [
            'code' => $tenant->code,
            'db'   => $tenant->database_name,
        ]);

        // Tag Sentry errors with the tenant so we can filter by centre
        if (app()->bound('sentry')) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($tenant): void {
                $scope->setTag('tenant.code', $tenant->code);
                $scope->setTag('tenant.uuid', $tenant->uuid);
            });
        }

        return $next($request);
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        $resolution = config('tenancy.resolution', 'subdomain');

        // Always resolve from subdomain first — the session may hold a stale
        // or unrelated tenant_uuid from a previous session on a different centre.
        if ($resolution === 'subdomain') {
            $tenant = $this->resolveFromSubdomain($request);
            if ($tenant) {
                return $tenant;
            }
        }

        // Fallback: session-stored UUID (local / non-subdomain environments only)
        if ($request->session()->has('tenant_uuid')) {
            return Tenant::where('uuid', $request->session()->get('tenant_uuid'))->first();
        }

        return null;
    }

    private function resolveFromSubdomain(Request $request): ?Tenant
    {
        $host       = $request->getHost();
        $domain     = config('tenancy.domain');
        $platform   = config('tenancy.platform_subdomain') . '.' . $domain;

        // Don't resolve tenant for the platform subdomain
        if ($host === $platform || $host === $domain) {
            return null;
        }

        // Check for custom domain first (e.g. app.medsouth.tt)
        if (! str_ends_with($host, '.' . $domain)) {
            return Tenant::where('custom_domain', $host)->first();
        }

        // Extract subdomain code: medsouth.ttmed.app → medsouth
        $subdomain = str_replace('.' . $domain, '', $host);

        return Tenant::where('code', strtolower($subdomain))->first();
    }
}
