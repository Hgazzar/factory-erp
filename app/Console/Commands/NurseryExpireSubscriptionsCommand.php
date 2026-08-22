<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TenantProfile;
use App\Services\Nursery\NurserySubscriptionService;
use App\Services\Tenant\TenantModuleRegistry;
use Illuminate\Console\Command;

final class NurseryExpireSubscriptionsCommand extends Command
{
    protected $signature = 'nursery:expire-subscriptions {--dry-run : Count without updating}';

    protected $description = 'Expire unpaid nursery subscriptions whose end date has passed.';

    public function handle(
        NurserySubscriptionService $subscriptions,
        TenantModuleRegistry $modules,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $profiles = TenantProfile::query()
            ->where('status', TenantProfile::STATUS_ACTIVE)
            ->where('niche_key', 'nurseries')
            ->get();

        $total = 0;

        foreach ($profiles as $profile) {
            $tenantId = (int) $profile->tenant_user_id;

            if (! $modules->isEnabled('nursery', $tenantId)) {
                continue;
            }

            if ($dryRun) {
                $this->line("Tenant {$tenantId}: would expire overdue unpaid subscriptions (dry-run).");

                continue;
            }

            $expired = $subscriptions->expireOverdueUnpaid($tenantId);
            $total += $expired;

            if ($expired > 0) {
                $this->info("Tenant {$tenantId}: expired {$expired} unpaid subscription(s).");
            }
        }

        $this->info("Done. Expired unpaid subscriptions: {$total}");

        return self::SUCCESS;
    }
}
