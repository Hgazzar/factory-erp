<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantModuleRegistry;
use App\Services\Tenant\TenantNavigationService;
use App\Services\Tenant\TenantThemeService;
use App\Support\AgentDebugLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * شاشة دخول مخصّصة لنظام الحضانة (ستايل Nursery).
     */
    public function createNursery(TenantThemeService $theme): View
    {
        $nurseryThemeVars = $theme->cssVariables(
            null,
            null,
            TenantThemeService::DEFAULT_PRIMARY,
            TenantThemeService::DEFAULT_SECONDARY,
            ['nursery', 'np', 'tenant'],
        );

        return view('auth.nursery-login', [
            'nurseryThemeVars' => $nurseryThemeVars,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // #region agent log
        AgentDebugLog::line('H_CONFIG', 'AuthenticatedSessionController@store', 'login_store_entry', [
            'cache_default' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'db_default' => config('database.default'),
        ]);
        // #endregion

        try {
            $request->authenticate();

            // #region agent log
            AgentDebugLog::line('H_AUTH', 'AuthenticatedSessionController@store', 'after_authenticate_ok', []);
            // #endregion

            $request->session()->regenerate();

            // #region agent log
            AgentDebugLog::line('H_SESSION', 'AuthenticatedSessionController@store', 'after_session_regenerate_ok', []);
            // #endregion

            $intendedUrl = app(TenantNavigationService::class)
                ->defaultHomeRoute($request->user());

            // #region agent log
            AgentDebugLog::line('H_REDIRECT', 'AuthenticatedSessionController@store', 'route_dashboard_resolved', [
                'path_len' => strlen($intendedUrl),
            ]);
            // #endregion

            return redirect()->intended($intendedUrl);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // #region agent log
            AgentDebugLog::line('H_EXCEPTION', 'AuthenticatedSessionController@store', 'login_store_throwable', [
                'class' => $e::class,
                'message' => $msg,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'finance_sql_hint' => (bool) preg_match('/\b(journal|journals|ledger|account|accounts|finance|posting|invoice|entries)\b/i', $msg),
            ]);
            // #endregion

            throw $e;
        }
    }

    /**
     * دخول عبر صفحة الحضانة فقط — يرفض حسابات غير Nursery.
     */
    public function storeNursery(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $navigation = app(TenantNavigationService::class);
        $modules = app(TenantModuleRegistry::class);
        $tenantUserId = app(TenantContext::class)->resolveTenantUserId($user);

        $isNursery = $tenantUserId !== null
            && $navigation->isNurseryPrimaryShell($tenantUserId)
            && $modules->isEnabled('nursery', $tenantUserId);

        if (! $isNursery) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'هذه الصفحة مخصّصة لحسابات نظام الحضانة فقط.',
            ]);
        }

        return redirect()->route('nursery.dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
