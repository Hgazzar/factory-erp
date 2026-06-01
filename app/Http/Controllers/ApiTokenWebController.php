<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Api\TenantApiTokenService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class ApiTokenWebController extends Controller
{
    public function index(TenantApiTokenService $tokenService, TenantContext $tenantContext): View
    {
        $user = auth()->user();
        $canManage = $tokenService->canManageTokens($user);

        return view('settings.api-tokens', [
            'tokens' => $canManage ? $tokenService->listTokens($user) : [],
            'canManageTokens' => $canManage,
            'tenantUserId' => $canManage ? $tenantContext->resolveTenantUserId($user) : null,
            'newPlainToken' => session('new_api_token'),
            'newTokenName' => session('new_api_token_name'),
        ]);
    }

    public function store(Request $request, TenantApiTokenService $tokenService): RedirectResponse
    {
        $user = auth()->user();

        if (! $tokenService->canManageTokens($user)) {
            return redirect()
                ->route('settings.api-tokens.index')
                ->with('error', 'لا يمكن إصدار توكن من هذا الحساب. استخدم حساب admin للمستأجر.');
        }

        $data = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
        ], [
            'device_name.required' => 'اسم التطبيق/الربط مطلوب.',
        ]);

        try {
            $token = $tokenService->createToken($user, $data['device_name']);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('settings.api-tokens.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('settings.api-tokens.index')
            ->with('success', 'تم إنشاء مفتاح الربط. انسخه الآن — لن يُعرض مرة أخرى.')
            ->with('new_api_token', $token->plainTextToken)
            ->with('new_api_token_name', $data['device_name']);
    }

    public function destroy(int $tokenId, TenantApiTokenService $tokenService): RedirectResponse
    {
        $user = auth()->user();

        if (! $tokenService->canManageTokens($user)) {
            abort(403);
        }

        if (! $tokenService->revokeToken($user, $tokenId)) {
            return redirect()
                ->route('settings.api-tokens.index')
                ->with('error', 'مفتاح الربط غير موجود أو تم إلغاؤه مسبقاً.');
        }

        return redirect()
            ->route('settings.api-tokens.index')
            ->with('success', 'تم إلغاء مفتاح الربط فوراً.');
    }
}
