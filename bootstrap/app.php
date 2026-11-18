<?php

use App\Exceptions\TenantInactiveException;
use App\Exceptions\TenantNotFoundException;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__ . '/../routes/web.php',
            __DIR__ . '/../routes/platform.php',
        ],
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'resolve.tenant'  => ResolveTenant::class,
            'tenant.active'   => EnsureTenantActive::class,
            'permission'      => CheckPermission::class,
        ]);

        // Encrypt session cookies
        $middleware->encryptCookies();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (TenantNotFoundException $e, Request $request) {
            return response()->view('errors.tenant-not-found', [], 404);
        });

        $exceptions->render(function (TenantInactiveException $e, Request $request) {
            return response()->view('errors.tenant-inactive', ['tenant' => $e->tenant], 403);
        });
    })->create();
