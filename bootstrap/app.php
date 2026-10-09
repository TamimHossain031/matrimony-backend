<?php

use App\Exceptions\DomainException;
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
    ->withMiddleware(function (Middleware $middleware): void {
        // The frontend is a cross-origin SPA that authenticates with Sanctum
        // personal access tokens (Bearer), not cookie/session auth. Enabling
        // statefulApi() makes requests from "stateful domains" (e.g.
        // localhost:3000) run the web session + CSRF stack, which breaks
        // token-based API calls with "CSRF token mismatch". Keep the API
        // stateless.
        // $middleware->statefulApi();
        $middleware->throttleApi();

        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureAccountActive::class,
            'staff' => \App\Http\Middleware\EnsureStaff::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Business-rule violations render as a clean 422.
        $exceptions->render(function (DomainException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], $e->status);
            }

            return null;
        });
    })->create();
