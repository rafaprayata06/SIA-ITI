<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public const AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';

    public const UNAUTHORIZED_TOKEN = 'UNAUTHORIZED_TOKEN';

    public const FORBIDDEN_ACCESS = 'FORBIDDEN_ACCESS';

    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';

    public const VALIDATION_ERROR = 'VALIDATION_ERROR';

    public static function success(mixed $data, string $message, int $code = 200, ?array $meta = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $code);
    }

    /**
     * @param  list<array{field: string, message: string}>  $details
     */
    public static function error(int $code, string $type, string $message, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'error' => [
                'type' => $type,
                'details' => $details,
            ],
        ], $code);
    }
}
