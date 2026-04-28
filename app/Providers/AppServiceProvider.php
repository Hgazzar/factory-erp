<?php

namespace App\Providers;

use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ProductionRecord;
use App\Models\User;
use App\Observers\JournalEntryObserver;
use App\Observers\JournalItemObserver;
use App\Observers\ProductionRecordObserver;
use App\Services\ChartOfAccountsProvisioner;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage_payroll', function (User $user): bool {
            return in_array($user->role, ['admin', 'supervisor'], true);
        });

        Notifications::alignment(Alignment::Center);
        Notifications::verticalAlignment(VerticalAlignment::End);

        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        ProductionRecord::observe(ProductionRecordObserver::class);
        JournalEntry::observe(JournalEntryObserver::class);
        JournalItem::observe(JournalItemObserver::class);

        Event::listen(Registered::class, function (Registered $event): void {
            ChartOfAccountsProvisioner::ensureForUser((int) $event->user->id);
        });

        View::share('defaultVatPercent', CompanySetting::resolvedDefaultVatPercent());
        View::share('erpCurrencyCode', CompanySetting::resolvedCurrencyCode());
        View::share('erpMoneyDecimals', (int) config('accounting.display_money_decimal_places', 2));
        View::share('erpQtyDecimals', (int) config('accounting.display_quantity_decimal_places', 2));
    }
}
