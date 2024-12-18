<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // معالجة استثناء RouteNotFoundException
        $this->renderable(function (RouteNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthorized.'], 401);
            }
        });

        // يمكنك إضافة استثناءات أخرى هنا إذا لزم الأمر.
    }
}
