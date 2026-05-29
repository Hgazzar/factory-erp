<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentMethodAccount;
use App\Models\PurchaseInvoice;
use App\Models\SalesPayment;
use App\Models\User;
use App\Services\InvoicePaymentRecordingService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\InvoicePaymentTestCase;

final class InvoicePaymentRecordingServiceTest extends InvoicePaymentTestCase
{
    private InvoicePaymentRecordingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvoicePaymentRecordingService::class);
    }

    #[Test]
    public function purchase_payment_creates_balanced_journal_for_payable_and_cash(): void
    {
        $invoice = $this->makePurchaseInvoice(total: 1000.0, paid: 200.0);

        $payment = $this->service->recordPurchaseInvoicePayment(
            $invoice,
            amount: 300.0,
            date: '2026-06-01',
            methodKey: PaymentMethodAccount::KEY_CASH,
            userId: (int) $this->tenant->id,
        );

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEqualsWithDelta(500.0, (float) $invoice->fresh()->paid_amount, 0.0001);
        $this->assertSame(PurchaseInvoice::STATUS_PARTIAL, $invoice->fresh()->status);

        $entry = JournalEntry::withoutGlobalScopes()->findOrFail($payment->journal_entry_id);
        $this->assertEqualsWithDelta(300.0, (float) $entry->total, 0.0001);
        $this->assertEqualsWithDelta(300.0, $this->journalLineAmount((int) $entry->id, $this->payableAccount->id, 'debit'), 0.0001);
        $this->assertEqualsWithDelta(300.0, $this->journalLineAmount((int) $entry->id, $this->cashAccount->id, 'credit'), 0.0001);
        $this->assertSame((int) $this->tenant->id, (int) $entry->user_id);
    }

    #[Test]
    public function purchase_payment_rejects_draft_invoice(): void
    {
        $invoice = $this->makePurchaseInvoice(total: 500.0, status: PurchaseInvoice::STATUS_DRAFT);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('لا يمكن تسجيل دفعة على فاتورة مسودة');

        $this->service->recordPurchaseInvoicePayment(
            $invoice,
            100.0,
            now()->toDateString(),
            PaymentMethodAccount::KEY_CASH,
            (int) $this->tenant->id,
        );
    }

    #[Test]
    public function purchase_payment_rejects_amount_exceeding_balance(): void
    {
        $invoice = $this->makePurchaseInvoice(total: 100.0, paid: 90.0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('المبلغ غير صالح أو يتجاوز الرصيد المتبقي');

        $this->service->recordPurchaseInvoicePayment(
            $invoice,
            20.0,
            now()->toDateString(),
            PaymentMethodAccount::KEY_CASH,
            (int) $this->tenant->id,
        );
    }

    #[Test]
    public function sales_payment_creates_balanced_journal_for_cash_and_receivable(): void
    {
        $invoice = $this->makeSalesInvoice(total: 800.0, paid: 0.0);

        $payment = $this->service->recordSalesInvoicePayment(
            $invoice,
            amount: 250.0,
            date: '2026-06-02',
            methodKey: PaymentMethodAccount::KEY_TRANSFER,
            userId: (int) $this->tenant->id,
        );

        $this->assertInstanceOf(SalesPayment::class, $payment);
        $this->assertEqualsWithDelta(250.0, (float) $invoice->fresh()->paid_amount, 0.0001);

        $entry = JournalEntry::withoutGlobalScopes()->findOrFail($payment->journal_entry_id);
        $this->assertEqualsWithDelta(250.0, (float) $entry->total, 0.0001);
        $this->assertEqualsWithDelta(250.0, $this->journalLineAmount((int) $entry->id, $this->bankAccount->id, 'debit'), 0.0001);
        $this->assertEqualsWithDelta(250.0, $this->journalLineAmount((int) $entry->id, $this->receivableAccount->id, 'credit'), 0.0001);
    }

    #[Test]
    public function sales_payment_rejects_draft_invoice(): void
    {
        $invoice = $this->makeSalesInvoice(total: 400.0, invoiceStatus: 'draft');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('لا يمكن تسجيل دفعة على فاتورة مسودة');

        $this->service->recordSalesInvoicePayment(
            $invoice,
            50.0,
            now()->toDateString(),
            PaymentMethodAccount::KEY_CASH,
            userId: (int) $this->tenant->id,
        );
    }

    #[Test]
    public function payment_journal_is_scoped_to_invoice_tenant(): void
    {
        $otherTenant = User::factory()->create(['role' => 'admin']);
        $this->seedPaymentLedgerAccountsFor($otherTenant);

        $invoice = $this->makePurchaseInvoice(total: 600.0);

        $payment = $this->service->recordPurchaseInvoicePayment(
            $invoice,
            100.0,
            now()->toDateString(),
            PaymentMethodAccount::KEY_CASH,
            (int) $this->tenant->id,
        );

        $entry = JournalEntry::withoutGlobalScopes()->findOrFail($payment->journal_entry_id);
        $this->assertSame((int) $this->tenant->id, (int) $entry->user_id);
        $this->assertEqualsWithDelta(
            100.0,
            $this->journalLineAmount((int) $entry->id, $this->payableAccount->id, 'debit'),
            0.0001
        );
    }

    private function seedPaymentLedgerAccountsFor(User $user): void
    {
        $cash = \App\Models\Account::factory()->forTenant($user)->asset()->create(['code' => '1010', 'name_ar' => 'صندوق B']);
        $bank = \App\Models\Account::factory()->forTenant($user)->asset()->create(['code' => '1020', 'name_ar' => 'بنك B']);
        \App\Models\Account::factory()->forTenant($user)->asset()->create(['code' => '1030', 'name_ar' => 'ذمم B']);
        \App\Models\Account::factory()->forTenant($user)->liability()->create(['code' => '2010', 'name_ar' => 'دائن B']);

        PaymentMethodAccount::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'method_key' => PaymentMethodAccount::KEY_CASH,
            'ledger_account_id' => $cash->id,
        ]);
    }
}
