<?php

use App\Http\Middleware\Cors;
use App\Http\Middleware\RestrictBrowserAccess;
use App\Http\Middleware\ExcludeDomainTracking;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(RestrictBrowserAccess::class);
        $middleware->append(Cors::class);
        $middleware->group('api', [
            Cors::class,
        ]);

        $middleware->alias([
            'exclude.domain.tracking' => ExcludeDomainTracking::class,
            'cors' => Cors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        });
    })->create();
