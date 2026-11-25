<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureRole;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.auth' => AuthenticateApiToken::class,
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Semua error API dipetakan ke lima tipe error kontrak (bagian 4.3).
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $details = [];

            foreach ($e->errors() as $field => $messages) {
                $details[] = ['field' => (string) $field, 'message' => (string) ($messages[0] ?? '')];
            }

            return ApiResponse::error(
                422,
                ApiResponse::VALIDATION_ERROR,
                'Validation failed for incoming request payload',
                $details,
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                401,
                ApiResponse::UNAUTHORIZED_TOKEN,
                'Token is missing or already invalidated',
            );
        });

        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(404, ApiResponse::RESOURCE_NOT_FOUND, 'Resource not found');
        });

        // 405 tidak ada di kontrak, jadi dilaporkan sebagai 404.
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(404, ApiResponse::RESOURCE_NOT_FOUND, 'Resource not found');
        });

        // 500 memakai tipe INTERNAL_SERVER_ERROR yang belum ada di kontrak. Amplopnya
        // hanya dipakai saat APP_DEBUG=false; kalau debug aktif, error dibiarkan apa adanya.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') || config('app.debug')) {
                return null;
            }

            return ApiResponse::error(500, 'INTERNAL_SERVER_ERROR', 'Internal server error');
        });
    })->create();
