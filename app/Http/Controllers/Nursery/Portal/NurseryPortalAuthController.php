<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery\Portal;

use App\Http\Controllers\Controller;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * مصادقة بوابة أولياء الأمور — رابط دعوة + رقم الجوال.
 */
final class NurseryPortalAuthController extends Controller
{
    /**
     * تأكيد رقم الجوال على رابط الدعوة وفتح الجلسة.
     */
    public function loginWithInvite(Request $request, NurseryPortalAuthService $auth): RedirectResponse
    {
        $tenantUserId = (int) $request->attributes->get('nursery_portal_tenant_user_id');
        $slug = (string) $request->route('tenant_slug');
        $token = (string) $request->route('token');

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        try {
            $auth->loginViaInvitePhone($tenantUserId, $token, (string) $validated['phone']);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.portal.home', ['tenant_slug' => $slug])
            ->with('success', 'تم تسجيل الدخول بنجاح.');
    }
}
