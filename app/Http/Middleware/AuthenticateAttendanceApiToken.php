<?php

namespace App\Http\Middleware;

use App\Models\AttendanceApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAttendanceApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'مطلوب رمز Bearer صالح.'], 401, [], JSON_UNESCAPED_UNICODE);
        }

        $plain = trim(substr($header, 7));
        $record = AttendanceApiToken::findValidByPlainToken($plain);
        if ($record === null) {
            return response()->json(['message' => 'رمز API غير صالح.'], 401, [], JSON_UNESCAPED_UNICODE);
        }

        $record->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('attendance_api_user_id', (int) $record->user_id);
        Auth::loginUsingId($record->user_id, false);

        return $next($request);
    }
}
