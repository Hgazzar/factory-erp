<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalItem;
use App\Models\PaymentMethodAccount;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\DefaultLedgerAccounts;

abstract class InvoicePaymentTestCase extends AccountingTestCase
{
    protected Account $cashAccount;

    protected Account $bankAccount;

    protected Account $receivableAccount;

    protected Account $payableAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPaymentLedgerAccounts();
    }

    protected function seedPaymentLedgerAccounts(): void
    {
        $this->cashAccount = Account::factory()->forTenant($this->tenant)->asset()->create([
            'code' => DefaultLedgerAccounts::CODE_CASH,
            'name_ar' => 'صندوق',
        ]);
        $this->bankAccount = Account::factory()->forTenant($this->tenant)->asset()->create([
            'code' => DefaultLedgerAccounts::CODE_BANK,
            'name_ar' => 'بنك',
        ]);
        $this->receivableAccount = Account::factory()->forTenant($this->tenant)->asset()->create([
            'code' => DefaultLedgerAccounts::CODE_AR,
            'name_ar' => 'ذمم مدينة',
        ]);
        $this->payableAccount = Account::factory()->forTenant($this->tenant)->liability()->create([
            'code' => DefaultLedgerAccounts::CODE_AP,
            'name_ar' => 'ذمم دائنة',
        ]);

        PaymentMethodAccount::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'method_key' => PaymentMethodAccount::KEY_CASH,
            'ledger_account_id' => $this->cashAccount->id,
        ]);
        PaymentMethodAccount::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'method_key' => PaymentMethodAccount::KEY_TRANSFER,
            'ledger_account_id' => $this->bankAccount->id,
        ]);
        PaymentMethodAccount::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'method_key' => PaymentMethodAccount::KEY_CARD,
            'ledger_account_id' => $this->bankAccount->id,
        ]);
    }

    protected function makeSupplier(?User $user = null): Supplier
    {
        $user ??= $this->tenant;
        $code = 'SUP-'.fake()->unique()->numerify('####');

        return Supplier::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'code' => $code,
            'name' => 'Supplier '.$code,
            'is_active' => true,
        ]);
    }

    protected function makeCustomer(?User $user = null): Customer
    {
        $user ??= $this->tenant;
        $code = 'CUS-'.fake()->unique()->numerify('####');

        return Customer::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'code' => $code,
            'name' => 'Customer '.$code,
            'status' => 'active',
            'crm_status' => 'active',
            'is_active' => true,
        ]);
    }

    protected function makePurchaseInvoice(
        float $total,
        float $paid = 0.0,
        string $status = PurchaseInvoice::STATUS_UNPAID,
        ?Supplier $supplier = null,
        ?Warehouse $warehouse = null,
    ): PurchaseInvoice {
        $supplier ??= $this->makeSupplier();
        $warehouse ??= Warehouse::factory()->forTenant($this->tenant)->create();

        return PurchaseInvoice::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'date' => now()->toDateString(),
            'reference' => 'PI-'.fake()->unique()->numerify('######'),
            'total' => $total,
            'paid_amount' => $paid,
            'status' => $status,
        ]);
    }

    protected function makeSalesInvoice(
        float $total,
        float $paid = 0.0,
        string $invoiceStatus = 'confirmed',
        ?Customer $customer = null,
        ?Warehouse $warehouse = null,
    ): SalesInvoice {
        $customer ??= $this->makeCustomer();
        $warehouse ??= Warehouse::factory()->forTenant($this->tenant)->create();

        return SalesInvoice::withoutGlobalScopes()->create([
            'user_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'date' => now()->toDateString(),
            'reference' => 'SI-'.fake()->unique()->numerify('######'),
            'invoice_status' => $invoiceStatus,
            'status' => $invoiceStatus === 'draft'
                ? SalesInvoice::STATUS_DRAFT
                : SalesInvoice::STATUS_UNPAID,
            'total' => $total,
            'paid_amount' => $paid,
        ]);
    }

    protected function journalLineAmount(int $journalEntryId, int $accountId, string $side): float
    {
        $column = $side === 'debit' ? 'debit' : 'credit';

        return (float) JournalItem::withoutGlobalScopes()
            ->where('journal_entry_id', $journalEntryId)
            ->where('account_id', $accountId)
            ->value($column);
    }
}
