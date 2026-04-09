<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * 200 – generic success
     */
    protected function successResponse(
        mixed  $data    = null,
        string $message = 'Success',
        int    $code    = 200
    ): JsonResponse {
        $payload = ['success' => true, 'message' => $message];

        if (!is_null($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $code);
    }

    /**
     * 201 – resource created
     */
    protected function createdResponse(
        mixed  $data    = null,
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * 422 – validation error
     */
    protected function validationErrorResponse(
        mixed  $errors  = null,
        string $message = 'Validation failed'
    ): JsonResponse {
        $payload = ['success' => false, 'message' => $message];

        if (!is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, 422);
    }

    /**
     * 404 – not found
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return response()->json(['success' => false, 'message' => $message], 404);
    }

    /**
     * 401 – unauthorized
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return response()->json(['success' => false, 'message' => $message], 401);
    }

    /**
     * 400 – bad request / business logic error
     */
    protected function errorResponse(
        string $message = 'An error occurred',
        int    $code    = 400,
        mixed  $errors  = null
    ): JsonResponse {
        $payload = ['success' => false, 'message' => $message];

        if (!is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }

    /**
     * 500 – server error
     */
    protected function serverErrorResponse(
        string $message = 'An unexpected error occurred. Please try again.'
    ): JsonResponse {
        return response()->json(['success' => false, 'message' => $message], 500);
    }

    /**
     * 200 – paginated list
     */
    protected function paginatedResponse(
        mixed  $paginator,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ], 200);
    }
}
