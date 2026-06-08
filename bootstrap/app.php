<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\SetLocaleToArabic::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\ApplyTenantNicheContext::class,
        ]);
        // Railway / أي reverse proxy: بدونها قد يُعرَّف الطلب كـ HTTP فيفشل كوكي الجلسة/CSRF (419).
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'module' => \App\Http\Middleware\EnsureTenantModuleEnabled::class,
            'technician_or_admin' => \App\Http\Middleware\EnsureUserIsTechnicianOrAdmin::class,
            'clinic.capability' => \App\Http\Middleware\EnsureClinicCapability::class,
            'nursery.capability' => \App\Http\Middleware\EnsureNurseryCapability::class,
            'fleet.capability' => \App\Http\Middleware\EnsureFleetCapability::class,
            'fleet.access' => \App\Http\Middleware\EnsureFleetOperationsEnabled::class,
            'clinic.portal.tenant' => \App\Http\Middleware\ResolveClinicPortalTenant::class,
            'nursery.portal.tenant' => \App\Http\Middleware\ResolveNurseryPortalTenant::class,
            'nursery.portal.guardian' => \App\Http\Middleware\EnsureNurseryPortalGuardian::class,
            'store.portal.tenant' => \App\Http\Middleware\ResolveStorePortalTenant::class,
            'feature' => \App\Http\Middleware\CheckFeature::class,
            'attendance.api' => \App\Http\Middleware\AuthenticateAttendanceApiToken::class,
            'worker.scope' => \App\Http\Middleware\EnsureWorkerRouteScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
