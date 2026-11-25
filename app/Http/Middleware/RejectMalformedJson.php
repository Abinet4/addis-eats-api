<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel silently treats an unparseable JSON body as an empty one, so a truncated
 * request would come back as "name is required". That blames the wrong thing:
 * the body could not be read at all, which is a 400, not a 422.
 */
final class RejectMalformedJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $body = $request->getContent();

        if ($request->isJson() && trim($body) !== '') {
            json_decode($body);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ApiResponse::error('malformed_json', 'The request body is not valid JSON.', 400, [
                    'reason' => json_last_error_msg(),
                ]);
            }
        }

        return $next($request);
    }
}
