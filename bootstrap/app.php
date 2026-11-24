<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Every failure under /api uses the same {"error": {code, message, meta}} envelope.
        $isApi = fn (Request $r) => $r->is('api/*');

        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $isApi($r) || $r->expectsJson());

        $exceptions->render(function (ValidationException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('validation_failed', 'The request was invalid.', 422, ['fields' => $e->errors()]);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $r) use ($isApi) {
            if (! $isApi($r)) {
                return null;
            }
            $prev = $e->getPrevious();
            if ($prev instanceof ModelNotFoundException) {
                $model = strtolower(class_basename($prev->getModel()));

                return ApiResponse::error('not_found', "No {$model} matches that identifier.", 404, ['ids' => $prev->getIds()]);
            }

            return ApiResponse::error('route_not_found', 'No endpoint matches this path.', 404, ['path' => '/'.$r->path()]);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('method_not_allowed', "{$r->method()} is not allowed on this path.", 405, [
                    'allow' => $e->getHeaders()['Allow'] ?? null,
                ])->withHeaders($e->getHeaders());
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('http_'.$e->getStatusCode(), $e->getMessage() ?: 'HTTP error.', $e->getStatusCode());
            }
        });
    })->create();
