<?php

namespace App\Http\Middleware;

use App\Support\ErpFilamentNotification;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحوّل session flash success إلى Filament Notifications لعرض موحّد.
 */
class ConvertFlashSuccessToFilament
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('success')) {
            $message = (string) $request->session()->pull('success', '');
            ErpFilamentNotification::fromLegacyFlashMessage($message);
        }

        return $next($request);
    }
}
