<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * استجابة JSON موحّدة لمسارات Headless (Phase 2).
 */
final class ApiResponse
{
    public static function success(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'status' => 'success',
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function error(string $message, int $status = 422, ?string $code = null): JsonResponse
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($code !== null) {
            $payload['code'] = $code;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  callable(mixed): array  $transformer
     */
    public static function paginated(LengthAwarePaginator $paginator, callable $transformer, array $extraData = []): JsonResponse
    {
        $items = $paginator->getCollection()->map($transformer)->values()->all();

        return self::success(
            array_merge($extraData, ['items' => $items]),
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }
}
