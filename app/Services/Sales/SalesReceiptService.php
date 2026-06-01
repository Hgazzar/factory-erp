<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\PaymentMethodAccount;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\SalesPaymentInvoice;
use App\Services\InvoicePaymentRecordingService;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * تحصيل مدفوعات العملاء — سداد فواتير أو دفعات عامة على ذمة العميل.
 */
final class SalesReceiptService
{
    public function __construct(
        private readonly InvoicePaymentRecordingService $paymentRecorder,
        private readonly SalesAccountingService $accounting,
    ) {}

    /**
     * @param  array{
     *     amount: float,
     *     payment_method: string,
     *     date: string,
     *     reference?: string|null,
     *     notes?: string|null,
     *     sales_invoice_id?: int|null,
     * }  $data
     */
    public function record(int $tenantUserId, Customer $customer, array $data): SalesPayment
    {
        $amount = (float) $data['amount'];
        $method = (string) $data['payment_method'];
        $date = (string) $data['date'];

        if ($amount <= 0) {
            throw new RuntimeException('مبلغ الدفعة يجب أن يكون أكبر من صفر.');
        }

        $invoiceId = isset($data['sales_invoice_id']) ? (int) $data['sales_invoice_id'] : null;

        if ($invoiceId) {
            $invoice = SalesInvoice::query()
                ->whereKey($invoiceId)
                ->where('customer_id', $customer->id)
                ->firstOrFail();

            return $this->paymentRecorder->recordSalesInvoicePayment(
                $invoice,
                $amount,
                $date,
                $method,
                $tenantUserId,
                $data['reference'] ?? null,
                $data['notes'] ?? null,
            );
        }

        return $this->recordWithAllocations($tenantUserId, $customer, [
            'amount' => $amount,
            'payment_method' => $method,
            'date' => $date,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'allocations' => [],
        ]);
    }

    /**
     * @param  array{
     *     amount: float,
     *     payment_method: string,
     *     date: string,
     *     reference?: string|null,
     *     notes?: string|null,
     *     allocations?: list<array{invoice_id: int, amount: float}>,
     * }  $data
     */
    public function recordWithAllocations(int $tenantUserId, Customer $customer, array $data): SalesPayment
    {
        $amount = (float) $data['amount'];
        $method = (string) $data['payment_method'];
        $date = (string) $data['date'];

        if ($amount <= 0) {
            throw new RuntimeException('مبلغ الدفعة يجب أن يكون أكبر من صفر.');
        }

        if (! in_array($method, [
            PaymentMethodAccount::KEY_CASH,
            PaymentMethodAccount::KEY_TRANSFER,
            PaymentMethodAccount::KEY_CARD,
        ], true)) {
            throw new RuntimeException('وسيلة الدفع غير صالحة.');
        }

        $allocations = collect($data['allocations'] ?? [])
            ->filter(fn (array $a) => (float) ($a['amount'] ?? 0) > 0)
            ->map(fn (array $a) => [
                'invoice_id' => (int) $a['invoice_id'],
                'amount' => (float) $a['amount'],
            ])
            ->values();

        $allocatedTotal = $allocations->sum('amount');
        if ($allocatedTotal > $amount + 0.0001) {
            throw new RuntimeException('مجموع المبالغ المخصصة لا يمكن أن يتجاوز مبلغ الدفعة.');
        }

        return DB::transaction(function () use ($tenantUserId, $customer, $data, $amount, $method, $date, $allocations): SalesPayment {
            PaymentMethodAccount::ensureDefaultsForUser($tenantUserId);

            $ledgerId = PaymentMethodAccount::ledgerAccountIdForMethod($tenantUserId, $method);
            $debitAccount = $ledgerId
                ? Account::withoutGlobalScopes()->where('user_id', $tenantUserId)->whereKey($ledgerId)->first()
                : null;

            if (! $debitAccount) {
                $debitAccount = $method === PaymentMethodAccount::KEY_CASH
                    ? DefaultLedgerAccounts::cashOnHand()
                    : DefaultLedgerAccounts::bankMain();
            }

            $receivableAccountId = $this->accounting->resolveReceivableAccountId($tenantUserId, $customer);

            $entry = JournalEntry::query()->create([
                'user_id' => $tenantUserId,
                'date' => $date,
                'reference' => 'PAY-CUST',
                'description' => 'دفعة من العميل — '.$customer->getLocalizedDisplayName(),
                'total' => $amount,
            ]);

            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $debitAccount->id,
                'description' => $this->debitDescription($method),
                'debit' => $amount,
                'credit' => 0,
            ]);

            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $receivableAccountId,
                'description' => 'تسوية رصيد العميل',
                'debit' => 0,
                'credit' => $amount,
            ]);

            $payment = SalesPayment::query()->create([
                'user_id' => $tenantUserId,
                'customer_id' => $customer->id,
                'date' => $date,
                'payment_method' => $method,
                'amount' => $amount,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'journal_entry_id' => $entry->id,
                'created_by' => (int) (auth()->id() ?? $tenantUserId),
            ]);

            $entry->reference = 'PAY-CUST-'.$payment->id;
            $entry->save();

            foreach ($allocations as $allocation) {
                $invoice = SalesInvoice::query()
                    ->whereKey($allocation['invoice_id'])
                    ->where('customer_id', $customer->id)
                    ->lockForUpdate()
                    ->first();

                if ($invoice === null) {
                    continue;
                }

                $balance = max(0, (float) $invoice->total - (float) ($invoice->paid_amount ?? 0));
                $allocAmount = min($allocation['amount'], $balance);
                if ($allocAmount <= 0) {
                    continue;
                }

                SalesPaymentInvoice::query()->create([
                    'user_id' => $tenantUserId,
                    'sales_payment_id' => $payment->id,
                    'sales_invoice_id' => $invoice->id,
                    'amount_allocated' => $allocAmount,
                ]);

                $invoice->increment('paid_amount', $allocAmount);
                $invoice->refresh();
                $invoice->refreshPaymentStatus();
                $invoice->save();

                Installment::distributePaymentToInvoice($invoice->id, $allocAmount);
            }

            return $payment;
        });
    }

    private function debitDescription(string $methodKey): string
    {
        return match ($methodKey) {
            PaymentMethodAccount::KEY_CASH => 'تحصيل نقدي من العميل',
            PaymentMethodAccount::KEY_TRANSFER => 'تحصيل بنكي من العميل',
            PaymentMethodAccount::KEY_CARD => 'تحصيل شبكة/بطاقة من العميل',
            default => 'تحصيل من العميل',
        };
    }
}
