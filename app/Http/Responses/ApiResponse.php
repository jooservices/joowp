<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * @param  array<mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        string $code,
        string $message,
        array $data = [],
        array $meta = [],
        int $status = 200
    ): JsonResponse {
        return self::make(true, $code, $status, $message, $data, $meta);
    }

    /**
     * @param  array<mixed>|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        string $code,
        string $message,
        array $meta = [],
        ?array $data = null,
        int $status = 400
    ): JsonResponse {
        return self::make(false, $code, $status, $message, $data, $meta);
    }

    /**
     * @param  array<mixed>|null  $data
     * @param  array<string, mixed>  $meta
     */
    private static function make(
        bool $ok,
        string $code,
        int $status,
        string $message,
        ?array $data,
        array $meta
    ): JsonResponse {
        return response()->json([
            'ok' => $ok,
            'code' => $code,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
        ], 200);
    }
}
