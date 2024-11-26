<?php

use App\Http\Middleware\CustomerMiddleware;
use App\Http\Middleware\DeliveryMiddleware;
use App\Http\Middleware\UserMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Router;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        using: function (Router $router) {

            $router->middleware('web')
                ->group(base_path('routes/web.php'));

            $router->middleware('api')
                ->prefix('api/v1')->group(function () use ($router) {
                    $router->prefix('/customer')
                        ->namespace('App\Http\Controllers\Api')
                        ->group(base_path('routes/api/v1/customer-api.php'));

                    $router->prefix('/delivery')
                        ->namespace('App\Http\Controllers\Api')
                        ->group(base_path('routes/api/v1/delivery-api.php'));

                    $router->prefix('/user')
                        ->namespace('App\Http\Controllers\Api')
                        ->group(base_path('routes/api/v1/user-api.php'));
                });
        },

    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'isCustomer' => CustomerMiddleware::class,
            'isDelivery' => DeliveryMiddleware::class,
            'isUser' => UserMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReportDuplicates();
    })->create();

