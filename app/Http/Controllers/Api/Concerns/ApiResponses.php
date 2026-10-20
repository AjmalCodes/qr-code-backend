<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Provides a consistent {success, message, data, errors} JSON envelope
 * for API responses across the Admin, Owner, Waiter, and Customer modules.
 */
trait ApiResponses
{
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    /**
     * Same {success, message, data, errors} envelope as success(), plus a
     * sibling "meta" key with pagination info — "data" stays the flat list
     * of items (already transformed, e.g. via Resource::collection()) so
     * existing consumers that just read "data" as an array keep working.
     */
    protected function successPaginated(mixed $data, LengthAwarePaginator $paginator, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'errors' => null,
        ], $status);
    }

    protected function error(string $message = 'Something went wrong', mixed $errors = null, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
