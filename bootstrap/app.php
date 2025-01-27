<?php

use App\Http\Middleware\JsonMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(JsonMiddleware::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {
           $exceptions->render(function (AuthenticationException $e, Request $request) {
                return response()->json([
                    'status_code' => 401,
                    'success' => false,
                    'message' => 'Unauthenticated.'
                  ], 401);
            });
    })->create();
