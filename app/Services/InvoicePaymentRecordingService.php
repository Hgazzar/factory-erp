<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\CompanySetting;
use App\Models\Installment;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\PaymentMethodAccount;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\SalesPaymentInvoice;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class InvoicePaymentRecordingService
{
    public function recordPurchaseInvoicePayment(
        PurchaseInvoice $invoice,
        float $amount,
        string $date,
        string $methodKey,
        int $userId,
        ?string $reference = null,
    ): Payment {
        if (! in_array($methodKey, [
            PaymentMethodAccount::KEY_CASH,
            PaymentMethodAccount::KEY_TRANSFER,
            PaymentMethodAccount::KEY_CARD,
        ], true)) {
            throw new RuntimeException('وسيلة الدفع غير صالحة.');
        }

        return DB::transaction(function () use ($invoice, $amount, $date, $methodKey, $userId, $reference) {
            /** @var PurchaseInvoice $invoice */
            $invoice = PurchaseInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->status === PurchaseInvoice::STATUS_DRAFT) {
                throw new RuntimeException('لا يمكن تسجيل دفعة على فاتورة مسودة.');
            }

            $balance = max(0, (float) $invoice->total - (float) ($invoice->paid_amount ?? 0));
            if ($amount <= 0 || $amount > $balance + 0.0001) {
                throw new RuntimeException('المبلغ غير صالح أو يتجاوز الرصيد المتبقي للفاتورة.');
            }

            PaymentMethodAccount::ensureDefaultsForUser($userId);
            $ledgerId = PaymentMethodAccount::ledgerAccountIdForMethod($userId, $methodKey);
            $creditAccount = $ledgerId
                ? Account::withoutGlobalScopes()->where('user_id', $userId)->whereKey($ledgerId)->first()
                : null;
            if (! $creditAccount) {
                $creditAccount = $methodKey === PaymentMethodAccount::KEY_CASH
                    ? DefaultLedgerAccounts::cashOnHand()
                    : DefaultLedgerAccounts::bankMain();
            }

            $payableAccount = $this->resolveDefaultPayableAccount($userId);

            $entry = JournalEntry::create([
                'user_id' => $userId,
                'date' => $date,
                'reference' => 'PMT',
                'description' => 'سند صرف مورد — فاتورة '.($invoice->reference ?: '#'.$invoice->id),
                'total' => $amount,
            ]);

            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $payableAccount->id,
                'description' => 'تخفيض ذمة المورد — فاتورة '.($invoice->reference ?: '#'.$invoice->id),
                'debit' => $amount,
                'credit' => 0,
            ]);
            $this->applyAccountPostingBalanceDelta($payableAccount, $amount, 0);

            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAccount->id,
                'description' => $this->paymentMethodJournalLineLabel($methodKey),
                'debit' => 0,
                'credit' => $amount,
            ]);
            $this->applyAccountPostingBalanceDelta($creditAccount, 0, $amount);

            $payment = Payment::query()->create([
                'user_id' => $userId,
                'supplier_id' => $invoice->supplier_id,
                'date' => $date,
                'reference' => $reference,
                'amount' => $amount,
                'type' => 'supplier',
                'payment_method' => $methodKey,
                'journal_entry_id' => $entry->id,
                'created_by' => $userId,
            ]);

            $entry->reference = 'PMT-'.$payment->id;
            $entry->save();

            $oldPaid = (float) ($invoice->paid_amount ?? 0);
            $oldStatus = (string) $invoice->status;
            $invoice->paid_amount = $oldPaid + $amount;
            $invoice->refreshPaymentStatus();
            $invoice->save();

            $payment->purchaseInvoices()->attach($invoice->id, ['amount' => $amount]);

            AuditTrail::log('update', 'purchase_invoices', $invoice->id, [
                'paid_amount' => $oldPaid,
                'status' => $oldStatus,
            ], [
                'paid_amount' => (float) $invoice->paid_amount,
                'status' => $invoice->status,
                'payment_id' => $payment->id,
                'payment_reference' => 'PMT-'.$payment->id,
            ]);

            return $payment;
        });
    }

    public function recordSalesInvoicePayment(
        SalesInvoice $invoice,
        float $amount,
        string $date,
        string $methodKey,
        int $userId,
        ?string $reference = null,
        ?string $notes = null,
    ): SalesPayment {
        if (! in_array($methodKey, [
            PaymentMethodAccount::KEY_CASH,
            PaymentMethodAccount::KEY_TRANSFER,
            PaymentMethodAccount::KEY_CARD,
        ], true)) {
            throw new RuntimeException('وسيلة الدفع غير صالحة.');
        }

        return DB::transaction(function () use ($invoice, $amount, $date, $methodKey, $userId, $reference, $notes) {
            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->status === SalesInvoice::STATUS_DRAFT || (string) ($invoice->invoice_status ?? '') === 'draft') {
                throw new RuntimeException('لا يمكن تسجيل دفعة على فاتورة مسودة.');
            }

            $balance = max(0, (float) $invoice->total - (float) ($invoice->paid_amount ?? 0));
            if ($amount <= 0 || $amount > $balance + 0.0001) {
                throw new RuntimeException('المبلغ غير صالح أو يتجاوز الرصيد المتبقي للفاتورة.');
            }

            PaymentMethodAccount::ensureDefaultsForUser($userId);
            $ledgerId = PaymentMethodAccount::ledgerAccountIdForMethod($userId, $methodKey);
            $debitAccount = $ledgerId
                ? Account::withoutGlobalScopes()->where('user_id', $userId)->whereKey($ledgerId)->first()
                : null;
            if (! $debitAccount) {
                $debitAccount = $methodKey === PaymentMethodAccount::KEY_CASH
                    ? DefaultLedgerAccounts::cashOnHand()
                    : DefaultLedgerAccounts::bankMain();
            }

            $receivableAccount = $this->resolveDefaultReceivableAccount($userId);

            $entry = JournalEntry::create([
                'user_id' => $userId,
                'date' => $date,
                'reference' => 'PAY-CUST',
                'description' => 'دفعة من العميل — فاتورة '.($invoice->reference ?: 'SINV-'.$invoice->id),
                'total' => $amount,
            ]);

            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $debitAccount->id,
                'description' => $this->salesDebitDescription($methodKey),
                'debit' => $amount,
                'credit' => 0,
            ]);
            $this->applyAccountPostingBalanceDelta($debitAccount, $amount, 0);

            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $receivableAccount->id,
                'description' => 'تسوية رصيد العميل — فاتورة '.($invoice->reference ?: 'SINV-'.$invoice->id),
                'debit' => 0,
                'credit' => $amount,
            ]);
            $this->applyAccountPostingBalanceDelta($receivableAccount, 0, $amount);

            $payment = SalesPayment::create([
                'user_id' => $userId,
                'customer_id' => $invoice->customer_id,
                'date' => $date,
                'payment_method' => $methodKey,
                'amount' => $amount,
                'reference' => $reference,
                'notes' => $notes,
                'journal_entry_id' => $entry->id,
                'created_by' => $userId,
            ]);

            SalesPaymentInvoice::create([
                'sales_payment_id' => $payment->id,
                'sales_invoice_id' => $invoice->id,
                'amount_allocated' => $amount,
            ]);

            $oldPaid = (float) ($invoice->paid_amount ?? 0);
            $invoice->increment('paid_amount', $amount);
            $invoice->refresh();
            $invoice->refreshPaymentStatus();
            $invoice->save();
            Installment::distributePaymentToInvoice($invoice->id, $amount);

            AuditTrail::log('update', 'sales_invoices', $invoice->id, [
                'paid_amount' => $oldPaid,
            ], [
                'paid_amount' => $oldPaid + $amount,
                'sales_payment_id' => $payment->id,
            ]);

            return $payment;
        });
    }

    public function resolveDefaultPayableAccount(int $userId): Account
    {
        $cs = CompanySetting::forTenant($userId);
        if ($cs && $cs->default_payable_account_id) {
            $acc = Account::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->whereKey($cs->default_payable_account_id)
                ->first();
            if ($acc) {
                return $acc;
            }
        }

        return DefaultLedgerAccounts::accountsPayable();
    }

    public function resolveDefaultReceivableAccount(int $userId): Account
    {
        $cs = CompanySetting::forTenant($userId);
        if ($cs && $cs->default_receivable_account_id) {
            $acc = Account::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->whereKey($cs->default_receivable_account_id)
                ->first();
            if ($acc) {
                return $acc;
            }
        }

        return DefaultLedgerAccounts::accountsReceivable();
    }

    private function paymentMethodJournalLineLabel(string $methodKey): string
    {
        return match ($methodKey) {
            PaymentMethodAccount::KEY_CASH => 'صرف نقدي (وسيلة الدفع)',
            PaymentMethodAccount::KEY_TRANSFER => 'صرف بنكي (وسيلة الدفع)',
            PaymentMethodAccount::KEY_CARD => 'صرف شبكة/بطاقة (وسيلة الدفع)',
            default => 'صرف (وسيلة الدفع)',
        };
    }

    private function salesDebitDescription(string $methodKey): string
    {
        return match ($methodKey) {
            PaymentMethodAccount::KEY_CASH => 'تحصيل نقدي من العميل',
            PaymentMethodAccount::KEY_TRANSFER => 'تحصيل بنكي من العميل',
            PaymentMethodAccount::KEY_CARD => 'تحصيل شبكة/بطاقة من العميل',
            default => 'تحصيل من العميل',
        };
    }

    private function applyAccountPostingBalanceDelta(Account $account, float $debit, float $credit): void
    {
        $delta = $this->postingDeltaByAccountType((string) $account->type, $debit, $credit);
        if (abs($delta) <= 0.0000001) {
            return;
        }

        Account::withoutGlobalScopes()
            ->where('user_id', (int) $account->user_id)
            ->whereKey($account->id)
            ->update([
                'current_balance' => DB::raw('current_balance + ('.(float) $delta.')'),
            ]);
    }

    private function postingDeltaByAccountType(string $accountType, float $debit, float $credit): float
    {
        $debitMinusCredit = $debit - $credit;

        return match ($accountType) {
            Account::TYPE_ASSET, Account::TYPE_EXPENSE => $debitMinusCredit,
            Account::TYPE_LIABILITY, Account::TYPE_REVENUE, Account::TYPE_EQUITY => -$debitMinusCredit,
            default => $debitMinusCredit,
        };
    }
}
