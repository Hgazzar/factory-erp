<?php

namespace App\Providers;

use App\Events\Clinic\ClinicAppointmentBooked;
use App\Listeners\Clinic\SendClinicAppointmentWhatsAppNotification;
use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\JournalItem;
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
use App\Services\Tenant\TenantModuleRegistry;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantFeatureRegistry;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage_payroll', function (User $user): bool {
            return $user->isAdminOrSuperAdmin() || $user->role === 'supervisor';
        });

        Notifications::alignment(Alignment::Center);
        Notifications::verticalAlignment(VerticalAlignment::End);

        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
            $appUrl = trim((string) config('app.url'));
            if ($appUrl !== '') {
                URL::forceRootUrl(rtrim($appUrl, '/'));
            }
        }

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
    }
}
