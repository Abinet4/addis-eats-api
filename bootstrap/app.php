<?php

declare(strict_types=1);

use App\Http\Middleware\RejectMalformedJson;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        $middleware->api(prepend: [RejectMalformedJson::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * One handler, one envelope: {"error": {"code", "message", "meta"}}.
         * Domain exceptions (OutsideZone, IllegalTransition, DishWithdrawn, ...) render
         * themselves through their own render() method, which Laravel calls first.
         * The callbacks below are tried in order, most specific first; Throwable is last.
         * The catalogue of codes is published in ERRORS.md.
         */
        $isApi = fn (Request $r): bool => $r->is('api/*');

        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $isApi($r) || $r->expectsJson());

        $exceptions->render(function (ValidationException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                // meta maps each failing field (lines.1.qty keeps its index) to its messages
                return ApiResponse::error('validation_failed', 'The given data was invalid.', 422, $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('unauthenticated', 'Authentication is required.', 401);
            }
        });

        // Laravel converts AuthorizationException into AccessDeniedHttpException before callbacks run.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('forbidden', 'You are not allowed to do this.', 403);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('rate_limited', 'Too many requests. Slow down and retry later.', 429)
                    ->withHeaders($e->getHeaders());
            }
        });

        // ModelNotFoundException arrives wrapped in a NotFoundHttpException.
        $exceptions->render(function (NotFoundHttpException $e, Request $r) use ($isApi) {
            if (! $isApi($r)) {
                return null;
            }
            $prev = $e->getPrevious();
            if ($prev instanceof ModelNotFoundException) {
                $model = Str::snake(class_basename($prev->getModel()), ' ');

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
                return ApiResponse::error('http_'.$e->getStatusCode(), $e->getMessage() ?: 'HTTP error.', $e->getStatusCode())
                    ->withHeaders($e->getHeaders());
            }
        });

        // Last: anything unexpected. Log the detail, return only a reference.
        $exceptions->render(function (Throwable $e, Request $r) use ($isApi) {
            if (! $isApi($r)) {
                return null;
            }
            $ref = 'err_'.Str::lower(Str::random(8));
            Log::error($e->getMessage(), ['ref' => $ref, 'exception' => $e, 'path' => $r->path()]);

            return ApiResponse::error('server_error', 'Something went wrong. Quote the reference to support.', 500, ['ref' => $ref]);
        });
    })->create();
