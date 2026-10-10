<?php

use App\Exceptions\DomainException;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Database\QueryException;
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
            'active' => EnsureAccountActive::class,
            'staff' => EnsureStaff::class,
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

        // Translate database constraint / missing default value issues into structured errors
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                if (str_contains($e->getMessage(), "doesn't have a default value")) {
                    preg_match("/Field '([^']+)' doesn't have a default value/", $e->getMessage(), $matches);
                    $field = $matches[1] ?? 'field';

                    return response()->json([
                        'message' => "The {$field} field is required.",
                        'errors' => [
                            $field => ["The {$field} field is required."],
                        ],
                    ], 422);
                }
            }

            return null;
        });
    })->create();
