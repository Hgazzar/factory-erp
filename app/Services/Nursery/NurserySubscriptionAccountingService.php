<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Nursery\Subscription;
use App\Services\FinancialRecordingService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\DefaultLedgerAccounts;
use App\Support\PremiumFeatureKeys;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

final class NurserySubscriptionAccountingService
{
    public function __construct(
        private readonly FinancialRecordingService $financial,
        private readonly TenantModuleRegistry $modules,
        private readonly TenantFeatureRegistry $features,
    ) {}

    public function canRecord(int $tenantUserId): bool
    {
        return $this->modules->isEnabled('finance', $tenantUserId)
            && $this->features->isEnabled(PremiumFeatureKeys::NURSERY_SUBSCRIPTION_FINANCE, $tenantUserId);
    }

    public function recordPaidSubscription(Subscription $subscription, int $tenantUserId, string $paymentMethod = 'cash'): ?JournalEntry
    {
        if (! $this->canRecord($tenantUserId)) {
            return null;
        }

        if ($subscription->journal_entry_id) {
            throw new RuntimeException('تم ترحيل قيد هذا الاشتراك مسبقاً.');
        }

        $amount = $subscription->finalAmount();

        if ($amount <= 0) {
            throw new InvalidArgumentException('مبلغ الاشتراك يجب أن يكون أكبر من صفر.');
        }

        $subscription->loadMissing(['child', 'plan']);

        $cashOrBank = DefaultLedgerAccounts::paymentSourceAssetForTenant($paymentMethod, $tenantUserId);
        $salesAccount = DefaultLedgerAccounts::salesRevenueForTenant($tenantUserId);

        $pct = CompanySetting::resolvedDefaultVatPercent($tenantUserId);
        $gross = $amount;
        $netRev = $gross;
        $vatAmt = 0.0;

        if ($pct > 0.00001) {
            $netRev = round($gross / (1 + $pct / 100), 4);
            $vatAmt = round($gross - $netRev, 4);
            if ($vatAmt < 0.0001) {
                $vatAmt = 0.0;
                $netRev = $gross;
            }
        }

        $reference = 'NUR-SUB-'.$subscription->id;
        $childName = (string) ($subscription->child?->name ?? '');
        $planName = (string) ($subscription->plan?->name ?? '');

        $lines = [
            [
                'account_id' => (int) $cashOrBank->id,
                'debit' => $gross,
                'credit' => 0,
                'description' => 'تحصيل اشتراك حضانة — '.$reference,
            ],
            [
                'account_id' => (int) $salesAccount->id,
                'debit' => 0,
                'credit' => $netRev,
                'description' => 'إيراد اشتراك — '.$planName,
            ],
        ];

        if ($vatAmt > 0.0001) {
            $vatAccount = DefaultLedgerAccounts::vatPayableForTenant($tenantUserId);
            $lines[] = [
                'account_id' => (int) $vatAccount->id,
                'debit' => 0,
                'credit' => $vatAmt,
                'description' => 'ضريبة قيمة مضافة — اشتراك حضانة',
            ];
        }

        $entry = $this->financial->recordBalancedJournal(
            $tenantUserId,
            now()->toDateString(),
            $reference,
            'اشتراك حضانة — '.$childName.' ('.$planName.')',
            $lines,
            (int) ($subscription->created_by ?? $tenantUserId),
        );

        $subscription->forceFill([
            'journal_entry_id' => $entry->id,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'is_paid' => true,
        ])->save();

        return $entry;
    }

    public function reversePaidSubscriptionIfNeeded(Subscription $subscription, int $tenantUserId): void
    {
        if ($subscription->reversal_journal_entry_id) {
            return;
        }

        if (! $subscription->journal_entry_id) {
            return;
        }

        if (! $this->canRecord($tenantUserId)) {
            return;
        }

        try {
            $original = JournalEntry::withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey((int) $subscription->journal_entry_id)
                ->first();

            if ($original === null) {
                return;
            }

            $items = JournalItem::withoutGlobalScopes()
                ->where('journal_entry_id', $original->id)
                ->get();

            if ($items->isEmpty()) {
                return;
            }

            $lines = [];
            foreach ($items as $item) {
                $lines[] = [
                    'account_id' => (int) $item->account_id,
                    'debit' => (float) $item->credit,
                    'credit' => (float) $item->debit,
                    'description' => 'عكس تحصيل اشتراك حضانة — NUR-SUB-'.$subscription->id,
                ];
            }

            $entry = $this->financial->recordBalancedJournal(
                $tenantUserId,
                now()->toDateString(),
                'NUR-SUB-'.$subscription->id.'-REV',
                'عكس اشتراك حضانة — NUR-SUB-'.$subscription->id,
                $lines,
                (int) ($subscription->created_by ?? $tenantUserId),
            );

            $subscription->forceFill([
                'reversal_journal_entry_id' => $entry->id,
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Nursery subscription journal reversal failed', [
                'subscription_id' => $subscription->id,
                'journal_entry_id' => $subscription->journal_entry_id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
