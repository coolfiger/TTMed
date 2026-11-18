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
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            throw new TenantNotFoundException();
        }

        if (! in_array($tenant->status, ['active', 'trial'])) {
            throw new TenantInactiveException($tenant);
        }

        $this->context->set($tenant);
        $this->dbManager->connect($tenant);

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

        // During authenticated requests, prefer the session-stored tenant
        // (avoids a platform DB lookup on every request)
        if ($request->session()->has('tenant_uuid')) {
            return Tenant::where('uuid', $request->session()->get('tenant_uuid'))
                ->first();
        }

        if ($resolution === 'subdomain') {
            return $this->resolveFromSubdomain($request);
        }

        // Session fallback (development / localhost)
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
