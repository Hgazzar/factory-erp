<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

trait RespondsWithBusinessJsonErrors
{
    protected function businessUnprocessable(Request $request, string $message): never
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 422));
        }

        abort(422, $message);
    }

    protected function businessForbidden(Request $request, string $message): never
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        abort(403, $message);
    }
}
