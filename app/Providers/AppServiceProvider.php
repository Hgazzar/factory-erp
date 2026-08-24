<?php

namespace App\Providers;

use App\Contracts\Core\Checkout\OnlineStoreCheckoutInterface;
use App\Contracts\Core\Payment\PaymentCredentialsProvider;
use App\Contracts\Core\Payment\PaymentGatewayInterface;
use App\Core\Messaging\PhoneNumberNormalizer;
use App\Core\Messaging\WhatsAppChannelFactory;
use App\Core\Messaging\WhatsAppConfigResolver;
use App\Core\Metrics\MetricsQueryRegistry;
use App\Core\Payment\ManualTransferGateway;
use App\Core\Payment\PaymentGatewayRegistry;
use App\Core\Payment\PaymobGateway;
use App\Core\Payment\PaymobHmacVerifier;
use App\Core\Payment\PaymobWebhookAuthenticator;
use App\Events\Clinic\ClinicAppointmentBooked;
use App\Listeners\Clinic\SendClinicAppointmentWhatsAppNotification;
use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Nursery\NurserySetting;
use App\Models\ProductionLog;
use App\Models\ProductionRecord;
use App\Models\StockMovement;
use App\Models\User;
use App\Observers\JournalEntryObserver;
use App\Observers\JournalItemObserver;
use App\Observers\ProductionLogObserver;
use App\Observers\ProductionRecordObserver;
use App\Observers\StockMovementObserver;
use App\Services\ChartOfAccountsProvisioner;
use App\Services\Store\Payment\StorePaymentCredentialsProvider;
use App\Services\Store\StoreCheckoutService;
use App\Services\Store\StoreMerchantMetricsService;
use App\Services\Tenant\TenantBrandingService;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Services\Tenant\TenantNavigationService;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayRegistry::class, function ($app): PaymentGatewayRegistry {
            $registry = new PaymentGatewayRegistry;

            foreach ([PaymobGateway::class, ManualTransferGateway::class] as $gatewayClass) {
                /** @var PaymentGatewayInterface $gateway */
                $gateway = $app->make($gatewayClass);
                $registry->register($gateway);
            }

            return $registry;
        });

        $this->app->singleton(PaymobHmacVerifier::class);
        $this->app->singleton(PaymobWebhookAuthenticator::class);
        $this->app->singleton(PaymentCredentialsProvider::class, StorePaymentCredentialsProvider::class);
        $this->app->bind(OnlineStoreCheckoutInterface::class, StoreCheckoutService::class);

        $this->app->singleton(MetricsQueryRegistry::class, function ($app): MetricsQueryRegistry {
            $registry = new MetricsQueryRegistry;
            $registry->register($app->make(StoreMerchantMetricsService::class));

            return $registry;
        });

        $this->app->singleton(WhatsAppConfigResolver::class);
        $this->app->singleton(PhoneNumberNormalizer::class);
        $this->app->singleton(WhatsAppChannelFactory::class);
    }

    public function boot(): void
    {
        Gate::define('manage_payroll', function (User $user): bool {
            return $user->isAdminOrSuperAdmin() || $user->role === 'supervisor';
        });

        Notifications::alignment(Alignment::Center);
        Notifications::verticalAlignment(VerticalAlignment::End);

        // Production behind Railway: never hard-pin a stale APP_URL (e.g. old
        // factory-erp-production.up.railway.app) — that 404s CSS/JS and breaks forms.
        if (config('app.env') === 'production' || filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) {
            URL::forceScheme('https');
            $rootUrl = $this->resolvePublicRootUrl();
            if ($rootUrl !== '') {
                URL::forceRootUrl($rootUrl);
            }
        }

        // Same-origin asset URLs so a wrong ASSET_URL/APP_URL cannot blank the UI.
        Vite::createAssetPathsUsing(
            static fn (string $path, ?bool $secure = null): string => '/'.ltrim($path, '/')
        );

        ProductionRecord::observe(ProductionRecordObserver::class);
        ProductionLog::observe(ProductionLogObserver::class);
        StockMovement::observe(StockMovementObserver::class);
        JournalEntry::observe(JournalEntryObserver::class);
        JournalItem::observe(JournalItemObserver::class);

        Event::listen(Registered::class, function (Registered $event): void {
            ChartOfAccountsProvisioner::ensureForUser((int) $event->user->id);
        });

        Event::listen(
            ClinicAppointmentBooked::class,
            SendClinicAppointmentWhatsAppNotification::class,
        );

        View::composer('*', function (\Illuminate\View\View $view): void {
            $enabledModules = auth()->check()
                ? app(TenantModuleRegistry::class)->enabledKeys()
                : ['core'];

            $view->with([
                'defaultVatPercent' => CompanySetting::resolvedDefaultVatPercent(),
                'erpCurrencyCode' => CompanySetting::resolvedCurrencyCode(),
                'enabledModules' => $enabledModules,
                'tenantNavigation' => auth()->check()
                    ? app(TenantNavigationService::class)
                    : null,
            ]);
        });

        View::share('erpMoneyDecimals', (int) config('accounting.display_money_decimal_places', 2));
        View::share('erpQtyDecimals', (int) config('accounting.display_quantity_decimal_places', 2));

        Blade::if('canFeature', function (string $featureKey): bool {
            $tenantId = app(TenantContext::class)->resolveTenantUserId();
            if ($tenantId === null) {
                return false;
            }

            return app(TenantFeatureRegistry::class)->isEnabled($featureKey, $tenantId);
        });

        View::composer('layouts.nursery-portal', function (\Illuminate\View\View $view): void {
            $tenantUserId = (int) ($view->getData()['tenantUserId'] ?? 0);
            $fallback = trim((string) ($view->getData()['nurseryName'] ?? ''));
            $this->composeTenantBranding(
                $view,
                $tenantUserId,
                $fallback !== '' ? $fallback : null,
                TenantBrandingService::MODULE_NURSERY,
            );
        });

        View::composer('layouts.nursery', function (\Illuminate\View\View $view): void {
            $tenantId = $this->resolveBrandingTenantUserId();
            if ($tenantId === null) {
                return;
            }
            $fallback = NurserySetting::query()->where('user_id', $tenantId)->value('nursery_name');
            $this->composeTenantBranding(
                $view,
                $tenantId,
                is_string($fallback) ? $fallback : null,
                TenantBrandingService::MODULE_NURSERY,
            );
        });

        View::composer('layouts.clinic', function (\Illuminate\View\View $view): void {
            $tenantId = $this->resolveBrandingTenantUserId();
            if ($tenantId === null) {
                return;
            }
            $this->composeTenantBranding($view, $tenantId, null, TenantBrandingService::MODULE_CLINIC);
        });

        View::composer('layouts.clinic-portal', function (\Illuminate\View\View $view): void {
            $tenantUserId = (int) request()->attributes->get('clinic_portal_tenant_user_id', 0);
            $this->composeTenantBranding($view, $tenantUserId, null, TenantBrandingService::MODULE_CLINIC);
        });

        View::composer('layouts.fleet', function (\Illuminate\View\View $view): void {
            $tenantId = $this->resolveBrandingTenantUserId();
            if ($tenantId === null) {
                return;
            }
            $this->composeTenantBranding($view, $tenantId, null, TenantBrandingService::MODULE_FLEET);
        });

        View::composer('layouts.store', function (\Illuminate\View\View $view): void {
            if ($view->offsetExists('tenantThemeVars')) {
                return;
            }
            $tenantUserId = (int) request()->attributes->get('store_portal_tenant_user_id', 0);
            if ($tenantUserId < 1) {
                return;
            }
            $branding = app(TenantBrandingService::class)->branding(
                $tenantUserId,
                null,
                TenantBrandingService::MODULE_STORE,
            );
            $view->with('tenantThemeVars', $branding['theme_vars']);
            if (! $view->offsetExists('tenantBrand')) {
                $view->with('tenantBrand', $branding);
            }
        });
    }

    /**
     * Public HTTPS origin for URL generation.
     * Prefer the live request host / Railway domain over a stale APP_URL
     * (Railway regenerates *.up.railway.app hostnames).
     */
    private function resolvePublicRootUrl(): string
    {
        if (! $this->app->runningInConsole()) {
            try {
                $host = request()->getHost();
                if ($host !== '' && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
                    return 'https://'.$host;
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        $railwayDomain = trim((string) env('RAILWAY_PUBLIC_DOMAIN', ''));
        if ($railwayDomain !== '') {
            return 'https://'.preg_replace('#^https?://#i', '', $railwayDomain);
        }

        return rtrim(trim((string) config('app.url')), '/');
    }

    private function resolveBrandingTenantUserId(): ?int
    {
        $tenantId = app(TenantContext::class)->resolveTenantUserId();
        if ($tenantId === null && auth()->check() && auth()->user()->role === 'admin') {
            $tenantId = (int) auth()->id();
        }

        return ($tenantId !== null && $tenantId > 0) ? $tenantId : null;
    }

    private function composeTenantBranding(
        \Illuminate\View\View $view,
        int $tenantUserId,
        ?string $fallbackName = null,
        string $module = TenantBrandingService::MODULE_TENANT,
    ): void {
        if ($tenantUserId < 1 || $view->offsetExists('tenantBrand')) {
            return;
        }

        $branding = app(TenantBrandingService::class)->branding($tenantUserId, $fallbackName, $module);

        $view->with([
            'tenantBrand' => $branding,
            'tenantThemeVars' => $branding['theme_vars'],
            'tenantDisplayName' => $branding['display_name'],
            'tenantLogoUrl' => $branding['logo_url'],
            'nurseryBrand' => $branding,
            'nurseryThemeVars' => $branding['theme_vars'],
            'nurseryDisplayName' => $branding['display_name'],
            'nurseryLogoUrl' => $branding['logo_url'],
            'nurseryName' => $fallbackName ?? $branding['fallback_name'],
        ]);
    }
}
