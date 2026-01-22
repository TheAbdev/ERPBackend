<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\EventServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register tenant-related middleware as aliases
        $middleware->alias([
            'tenant.resolve' => \App\Http\Middleware\ResolveTenant::class,
            'tenant.access' => \App\Http\Middleware\EnsureTenantAccess::class,
            'tenant.rate_limit' => \App\Http\Middleware\TenantRateLimit::class,
            'platform.owner' => \App\Http\Middleware\PlatformOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Global exception handling
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::validation(
                    $e->errors(),
                    $e->getMessage()
                );
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::unauthorized(
                    'Authentication required'
                );
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::forbidden(
                    $e->getMessage() ?: 'You do not have permission to perform this action'
                );
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::notFound(
                    'Resource not found'
                );
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::notFound(
                    'Endpoint not found'
                );
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::error(
                    'Too many requests. Please try again later.',
                    429,
                    null,
                    'TOO_MANY_REQUESTS',
                    ['retry_after' => $e->getHeaders()['Retry-After'] ?? null]
                );
            }
        });

        // Log all exceptions
        $exceptions->report(function (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_id' => request()->header('X-Request-ID'),
                'tenant_id' => app(\App\Core\Services\TenantContext::class)->getTenantId(),
            ]);
        });
    })->create();
