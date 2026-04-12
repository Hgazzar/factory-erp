<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * واجهة ERP بالعربية: فرض locale للتحقق والترجمة بغض النظر عن APP_LOCALE في .env.
 */
class SetLocaleToArabic
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ar');

        return $next($request);
    }
}
