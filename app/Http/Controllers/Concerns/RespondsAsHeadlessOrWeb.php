<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controllers هجينة: Blade للويب، JSON للـ Headless (Accept: application/json).
 */
trait RespondsAsHeadlessOrWeb
{
    protected function wantsApiResponse(Request $request): bool
    {
        return $request->is('api/*')
            || $request->expectsJson()
            || $request->wantsJson();
    }

    protected function apiOrWeb(Request $request, callable $api, callable $web): JsonResponse|\Illuminate\View\View|\Symfony\Component\HttpFoundation\Response
    {
        if ($this->wantsApiResponse($request)) {
            return $api();
        }

        return $web();
    }
}
