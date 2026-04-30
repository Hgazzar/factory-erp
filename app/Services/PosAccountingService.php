<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\PosSale;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * ترحيل قيود نقطة البيع ضمن دليل الحسابات القياسي:
 * مدين تحصيل (خزينة/بنك)، دائن إيراد مبيعات (صافي بعد ضريبة القيمة المضافة الشاملة)،
 * دائن ضريبة القيمة المضافة المستحقة عندما تنطبق النسبة من إعداد المنشأة،
 * مدين تكلفة البضاعة، دائن مخزون منتج تام.
 *
 * يُفترض أن أسعار نقطة البيع شاملة للضريبة عندما تكون النسبة أكبر من صفر — بنفس منطق الفوترة المحلية الشائع.
 */
final class PosAccountingService
{
    public function __construct(
        private readonly FinancialRecordingService $financialRecording,
    ) {
    }

    public function recordJournalForPosSale(PosSale $sale): JournalEntry
    {
        return DB::transaction(function () use ($sale): JournalEntry {
            $locked = PosSale::withoutGlobalScopes()
                ->whereKey($sale->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->journal_entry_id) {
                throw new InvalidArgumentException('تم ترحيل قيد محاسبي لهذا الإيصال مسبقاً.');
            }

            $locked->loadMissing(['lines']);

            $totalRev = round((float) $locked->total_price, 4);
            if ($totalRev <= 0) {
                throw new InvalidArgumentException('إجمالي الإيصال يجب أن يكون أكبر من صفر لترحيل القيد.');
            }

            $totalCogs = round($locked->lines->sum(function ($line) {
                return round((float) $line->quantity * (float) $line->unit_cost, 4);
            }), 4);

            if ($totalCogs <= 0) {
                throw new InvalidArgumentException('تكلفة البضاعة المباعة غير صالحة؛ تحقق من تكلفة الأصناف.');
            }

            $pct = CompanySetting::resolvedDefaultVatPercent((int) $locked->user_id);
            $netRev = $totalRev;
            $vatAmt = 0.0;
            if ($pct > 0.00001) {
                $netRev = round($totalRev / (1 + $pct / 100), 4);
                $vatAmt = round($totalRev - $netRev, 4);
                if ($vatAmt < 0.0001) {
                    $vatAmt = 0.0;
                    $netRev = $totalRev;
                }
            }

            $paymentMethod = $this->mapPaymentMethodToLedgerKey((string) $locked->payment_method);
            $cashOrBank = DefaultLedgerAccounts::paymentSourceAsset($paymentMethod);

            $salesAccount = DefaultLedgerAccounts::salesRevenue();
            $vatAccount = DefaultLedgerAccounts::vatPayable();
            $cogsAccount = DefaultLedgerAccounts::ensureCogsRoot();
            $finishedGoodsInv = DefaultLedgerAccounts::inventoryFinishedGoods();

            $lines = [
                [
                    'account_id' => $cashOrBank->id,
                    'debit' => $totalRev,
                    'credit' => 0,
                    'description' => 'تحصيل نقطة بيع — '.$locked->receipt_number,
                ],
                [
                    'account_id' => $salesAccount->id,
                    'debit' => 0,
                    'credit' => $netRev,
                    'description' => 'إيراد مبيعات نقطة بيع (صافي) — '.$locked->receipt_number,
                ],
            ];

            if ($vatAmt > 0.0001) {
                $lines[] = [
                    'account_id' => $vatAccount->id,
                    'debit' => 0,
                    'credit' => $vatAmt,
                    'description' => 'ضريبة القيمة المضافة مستحقة — '.$locked->receipt_number,
                ];
            }

            $lines[] = [
                'account_id' => $cogsAccount->id,
                'debit' => $totalCogs,
                'credit' => 0,
                'description' => 'تكلفة بضاعة مباعة — '.$locked->receipt_number,
            ];
            $lines[] = [
                'account_id' => $finishedGoodsInv->id,
                'debit' => 0,
                'credit' => $totalCogs,
                'description' => 'خفض مخزون منتج تام — '.$locked->receipt_number,
            ];

            $entry = $this->financialRecording->recordBalancedJournal(
                (int) $locked->user_id,
                $locked->created_at->toDateString(),
                'POS-'.$locked->receipt_number,
                'بيع نقطة بيع '.$locked->receipt_number,
                $lines,
                auth()->id() ? (int) auth()->id() : null,
            );

            $locked->forceFill(['journal_entry_id' => $entry->id])->save();

            return $entry;
        });
    }

    /**
     * card يُعامل كتحصيل بنكي؛ mixed/other يُعامل كنقدية افتراضياً حتى يُضاعف تقسيم المبالغ لاحقاً.
     */
    private function mapPaymentMethodToLedgerKey(string $method): string
    {
        return match ($method) {
            'bank', 'card' => 'bank',
            default => 'cash',
        };
    }
}
