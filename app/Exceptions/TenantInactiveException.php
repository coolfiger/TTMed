<?php

namespace App\Exceptions;

use App\Models\Platform\Tenant;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantInactiveException extends HttpException
{
    public function __construct(public readonly Tenant $tenant)
    {
        $message = match ($tenant->status) {
            'suspended'   => 'This account has been suspended. Please contact support.',
            'deactivated' => 'This account has been deactivated.',
            default       => 'This account is not currently active.',
        };

        parent::__construct(403, $message);
    }
}
