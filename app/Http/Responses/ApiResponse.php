<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

/**
 * The one place that decides what a response envelope looks like (Day 56).
 *
 * Two deliberate fixes against the slide version:
 *  - ok() does not use array_filter(): that would delete "data" when it is an
 *    empty list, so an empty search result would come back as {} instead of {"data": []}.
 *  - error() uses the key "message", not compact('msg'), so the body really is
 *    {"error": {"code", "message", "meta"}} as the envelope slide promises.
 */
final class ApiResponse
{
    public static function ok(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        $body = ['data' => $data];
        if ($meta !== []) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    public static function created(mixed $data, string $location): JsonResponse
    {
        return response()->json(['data' => $data], 201)->header('Location', $location);
    }

    /** A paginated resource collection already carries data, links and meta. */
    public static function paginated(ResourceCollection $collection): JsonResponse
    {
        return $collection->response();
    }

    public static function noContent(): Response
    {
        return response()->noContent();
    }

    public static function error(string $code, string $message, int $status, array $meta = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'meta' => (object) $meta, // always an object, {} when empty
            ],
        ], $status);
    }
}
