<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TenantProfile;
use App\Services\Nursery\NurserySubscriptionService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\PremiumFeatureKeys;
use Illuminate\Console\Command;

final class NurserySendWhatsAppRemindersCommand extends Command
{
    protected $signature = 'nursery:send-whatsapp-reminders {--dry-run : Count without sending}';

    protected $description = 'Send nursery subscription payment and renewal WhatsApp reminders.';

    public function handle(
        NurserySubscriptionService $subscriptions,
        TenantFeatureRegistry $features,
        TenantModuleRegistry $modules,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $profiles = TenantProfile::query()
            ->where('status', TenantProfile::STATUS_ACTIVE)
            ->where('niche_key', 'nurseries')
            ->get();

        $totalPayment = 0;
        $totalRenewal = 0;

        foreach ($profiles as $profile) {
            $tenantId = (int) $profile->tenant_user_id;

            if (! $modules->isEnabled('nursery', $tenantId)) {
                continue;
            }

            if (! $features->isEnabled(PremiumFeatureKeys::NURSERY_WHATSAPP_AUTOMATION, $tenantId)) {
                continue;
            }

            if ($dryRun) {
                $this->line("Tenant {$tenantId}: would process reminders (dry-run).");

                continue;
            }

            $payment = $subscriptions->sendPaymentReminders($tenantId);
            $renewal = $subscriptions->sendRenewalReminders($tenantId);

            $totalPayment += $payment['queued'];
            $totalRenewal += $renewal['queued'];

            if ($payment['queued'] > 0 || $renewal['queued'] > 0) {
                $this->info("Tenant {$tenantId}: payment queued {$payment['queued']}, renewal queued {$renewal['queued']}");
            }
        }

        $this->info("Done. Payment reminders queued: {$totalPayment}, renewal: {$totalRenewal}");

        return self::SUCCESS;
    }
}
