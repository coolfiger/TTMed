<?php

namespace App\Http\Middleware;

use App\Exceptions\TenantInactiveException;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->get();

        if ($tenant && ! $tenant->isActive()) {
            throw new TenantInactiveException($tenant);
        }

        return $next($request);
    }
}
