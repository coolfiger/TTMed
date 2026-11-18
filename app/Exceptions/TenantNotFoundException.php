<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Medical centre not found.');
    }
}
