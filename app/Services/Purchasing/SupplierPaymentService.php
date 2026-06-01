<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\PaymentMethodAccount;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\InvoicePaymentRecordingService;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * مدفوعات الموردين — سداد فواتير أو دفعات عامة على ذمة المورد.
 */
final class SupplierPaymentService
{
    public function __construct(
        private readonly InvoicePaymentRecordingService $paymentRecorder,
    ) {}

    /**
     * @param  array{
     *     amount: float,
     *     payment_method: string,
     *     date: string,
     *     reference?: string|null,
     *     notes?: string|null,
     *     purchase_invoice_id?: int|null,
     * }  $data
     */
    public function record(int $tenantUserId, Supplier $supplier, array $data): Payment
    {
        $amount = (float) $data['amount'];
        $method = (string) $data['payment_method'];
        $date = (string) $data['date'];

        if ($amount <= 0) {
            throw new RuntimeException('مبلغ الدفعة يجب أن يكون أكبر من صفر.');
        }

        $invoiceId = isset($data['purchase_invoice_id']) ? (int) $data['purchase_invoice_id'] : null;

        if ($invoiceId) {
            $invoice = PurchaseInvoice::query()
                ->whereKey($invoiceId)
                ->where('supplier_id', $supplier->id)
                ->firstOrFail();

            return $this->paymentRecorder->recordPurchaseInvoicePayment(
                $invoice,
                $amount,
                $date,
                $method,
                $tenantUserId,
                $data['reference'] ?? null,
            );
        }

        return $this->recordGeneralPayment(
            $tenantUserId,
            $supplier,
            $amount,
            $date,
            $method,
            $data['reference'] ?? null,
            $data['notes'] ?? null,
        );
    }

    private function recordGeneralPayment(
        int $tenantUserId,
        Supplier $supplier,
        float $amount,
        string $date,
        string $methodKey,
        ?string $reference,
        ?string $notes,
    ): Payment {
        if (! in_array($methodKey, [
            PaymentMethodAccount::KEY_CASH,
            PaymentMethodAccount::KEY_TRANSFER,
            PaymentMethodAccount::KEY_CARD,
        ], true)) {
            throw new RuntimeException('وسيلة الدفع غير صالحة.');
        }

        return DB::transaction(function () use ($tenantUserId, $supplier, $amount, $date, $methodKey, $reference, $notes): Payment {
            PaymentMethodAccount::ensureDefaultsForUser($tenantUserId);

            $ledgerId = PaymentMethodAccount::ledgerAccountIdForMethod($tenantUserId, $methodKey);
            $creditAccount = $ledgerId
                ? Account::withoutGlobalScopes()->where('user_id', $tenantUserId)->whereKey($ledgerId)->first()
                : null;

            if (! $creditAccount) {
                $creditAccount = $methodKey === PaymentMethodAccount::KEY_CASH
                    ? DefaultLedgerAccounts::cashOnHand()
                    : DefaultLedgerAccounts::bankMain();
            }

            $payableAccount = app(PurchaseAccountingService::class)->resolvePayableAccountId($tenantUserId, $supplier);

            $entry = JournalEntry::query()->create([
                'user_id' => $tenantUserId,
                'date' => $date,
                'reference' => 'PMT',
                'description' => 'سند صرف مورد — '.$supplier->getLocalizedDisplayName(),
                'total' => $amount,
            ]);

            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'user_id' => $tenantUserId,
                'account_id' => $payableAccount,
                'description' => 'تسوية ذمة المورد',
                'debit' => $amount,
                'credit' => 0,
            ]);

            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'user_id' => $tenantUserId,
                'account_id' => $creditAccount->id,
                'description' => 'صرف من الخزينة/البنك',
                'debit' => 0,
                'credit' => $amount,
            ]);

            $payment = Payment::query()->create([
                'user_id' => $tenantUserId,
                'supplier_id' => $supplier->id,
                'date' => $date,
                'reference' => $reference,
                'amount' => $amount,
                'type' => 'supplier',
                'payment_method' => $methodKey,
                'notes' => $notes,
                'journal_entry_id' => $entry->id,
                'created_by' => $tenantUserId,
            ]);

            $entry->reference = 'PMT-'.$payment->id;
            $entry->save();

            return $payment;
        });
    }
}
