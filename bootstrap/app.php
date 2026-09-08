<?php

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
        $middleware->alias([
            'tenant.scoped' => \App\Http\Middleware\EnsureTenantFromToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always render JSON for API routes
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Custom exception handlers for API responses
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // Handle authentication exceptions
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated',
                        'error' => 'Missing or invalid JWT token',
                    ], 401);
                }

                // Handle authorization exceptions
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                        'error' => $e->getMessage(),
                    ], 403);
                }

                // Handle model not found exceptions
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resource not found',
                        'error' => 'The requested resource could not be found',
                    ], 404);
                }

                // Handle validation exceptions
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Handle database exceptions
                if ($e instanceof \PDOException || $e instanceof \Illuminate\Database\QueryException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Database error',
                        'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
                    ], 500);
                }

                // Handle generic exceptions in debug mode
                if (config('app.debug')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An error occurred',
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ], 500);
                }

                // Generic production error
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred',
                    'error' => 'Server error',
                ], 500);
            }
        });
    })->create();
