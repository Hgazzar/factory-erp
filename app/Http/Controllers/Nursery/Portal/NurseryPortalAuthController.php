<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery\Portal;

use App\Http\Controllers\Controller;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * مصادقة بوابة أولياء الأمور — طلب OTP والتحقق منه.
 */
final class NurseryPortalAuthController extends Controller
{
    /**
     * يطلب رمز تحقق ويجدول إرساله عبر واتساب.
     */
    public function requestOtp(Request $request, NurseryPortalAuthService $auth): RedirectResponse
    {
        $tenantUserId = (int) $request->attributes->get('nursery_portal_tenant_user_id');
        $slug = (string) $request->route('tenant_slug');

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        try {
            $auth->requestOtp($tenantUserId, (string) $validated['phone']);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return back()
            ->withInput()
            ->with('otp_sent', true)
            ->with('success', 'تم إنشاء رمز التحقق. ستتم جدولة إرساله عبر واتساب.');
    }

    /**
     * يتحقق من الرمز ويفتح جلسة ولي الأمر.
     */
    public function verifyOtp(Request $request, NurseryPortalAuthService $auth): RedirectResponse
    {
        $tenantUserId = (int) $request->attributes->get('nursery_portal_tenant_user_id');
        $slug = (string) $request->route('tenant_slug');

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'otp' => ['required', 'string', 'max:16'],
        ]);

        try {
            $auth->verifyOtp($tenantUserId, (string) $validated['phone'], (string) $validated['otp']);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('otp_sent', true)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.portal.home', ['tenant_slug' => $slug])
            ->with('success', 'تم تسجيل الدخول بنجاح.');
    }
}
