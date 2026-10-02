<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * One JSON shape for the whole farmer API, matching Laravel's own
 * validation/auth error responses:
 *
 *   reads:   { "data": ..., "meta": { pagination } }
 *   actions: { "message": "...", "data": ... }
 *   errors:  { "message": "...", "errors": { ... } }   + HTTP status
 */
trait ApiResponse
{
    protected function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        if ($data instanceof LengthAwarePaginator) {
            return $this->paginated($data, $message, $code);
        }

        $body = [];
        if ($message !== null) {
            $body['message'] = $message;
        }
        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $code);
    }

    protected function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function paginated(LengthAwarePaginator $paginator, ?string $message = null, int $code = 200): JsonResponse
    {
        $body = [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
        if ($message !== null) {
            $body = ['message' => $message] + $body;
        }

        return response()->json($body, $code);
    }

    protected function error(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $body = ['message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $code);
    }

    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function forbidden(string $message = 'Access denied.'): JsonResponse
    {
        return $this->error($message, 403);
    }

    protected function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return $this->error($message, 401);
    }
}
