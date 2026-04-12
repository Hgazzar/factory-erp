<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AgentDebugLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            $dashboardUrl = route('dashboard', absolute: false);

            // #region agent log
            AgentDebugLog::line('H_REDIRECT', 'AuthenticatedSessionController@store', 'route_dashboard_resolved', [
                'path_len' => strlen($dashboardUrl),
            ]);
            // #endregion

            return redirect()->intended($dashboardUrl);
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
