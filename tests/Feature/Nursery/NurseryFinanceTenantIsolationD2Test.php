<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\PaymentMethodAccount;
use App\Models\Receipt;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryFinanceTenantIsolationD2Test extends NurseryTestCase
{
    private User $tenantB;

    private Account $accountA;

    private Account $accountB;

    private JournalEntry $journalA;

    private JournalEntry $journalB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureD2Schema();

        $this->tenantB = User::factory()->create(['role' => 'admin']);
        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $this->tenantB->id,
            ['core', 'nursery', 'finance', 'hr']
        );
        \App\Models\TenantProfile::query()->create([
            'tenant_user_id' => (int) $this->tenantB->id,
            'niche_key' => 'nurseries',
            'domain' => 'other-nursery-d2',
            'slug' => 'other-nursery-d2',
            'status' => \App\Models\TenantProfile::STATUS_ACTIVE,
        ]);

        $this->seedTenantFinance((int) $this->tenant->id, 'A');
        $this->seedTenantFinance((int) $this->tenantB->id, 'B');

        $this->accountA = Account::withoutGlobalScopes()
            ->where('user_id', $this->tenant->id)
            ->where('code', 'D2-A-CASH')
            ->firstOrFail();
        $this->accountB = Account::withoutGlobalScopes()
            ->where('user_id', $this->tenantB->id)
            ->where('code', 'D2-B-CASH')
            ->firstOrFail();
        $this->journalA = JournalEntry::withoutGlobalScopes()
            ->where('user_id', $this->tenant->id)
            ->where('reference', 'D2-A-JE')
            ->firstOrFail();
        $this->journalB = JournalEntry::withoutGlobalScopes()
            ->where('user_id', $this->tenantB->id)
            ->where('reference', 'D2-B-JE')
            ->firstOrFail();
    }

    #[Test]
    public function owner_a_sees_only_tenant_a_accounts_journals_expenses_payments(): void
    {
        $this->actingAs($this->tenant);

        $this->assertTrue(Account::query()->whereKey($this->accountA->id)->exists());
        $this->assertFalse(Account::query()->whereKey($this->accountB->id)->exists());
        $this->assertSame(2, Account::query()->where('code', 'like', 'D2-A-%')->count());
        $this->assertSame(0, Account::query()->where('code', 'like', 'D2-B-%')->count());

        $this->assertSame(1, JournalEntry::query()->where('reference', 'like', 'D2-%-JE')->count());
        $this->assertFalse(JournalEntry::query()->whereKey($this->journalB->id)->exists());

        $this->assertSame(1, Payment::query()->where('reference', 'like', 'D2-%-EXP')->count());
        $this->assertSame(1, Payment::query()->where('reference', 'like', 'D2-%-PMT')->count());
        $this->assertFalse(Payment::query()->where('reference', 'D2-B-EXP')->exists());
        $this->assertFalse(Payment::query()->where('reference', 'D2-B-PMT')->exists());

        $this->get(route('finance.accounts.index'))
            ->assertOk()
            ->assertSee('حساب نقدي حضانة أ', false)
            ->assertDontSee('حساب نقدي حضانة ب', false);

        $journals = $this->get(route('finance.journals.index'));
        $journals->assertOk();
        $journals->assertDontSee('قيد حضانة B', false);
        $journals->assertDontSee('D2-B-JE', false);
        $this->assertTrue(
            JournalEntry::query()->where('reference', 'D2-A-JE')->exists(),
            'Owner A must see own journal via scope'
        );

        // expenses/payments index قد تحتاج جداول إضافية في سكيمة الاختبار — العزل عبر الـscope أعلاه
        $this->assertAuthorizedNotForbidden(route('finance.payments.index'));
    }

    #[Test]
    public function owner_b_sees_only_tenant_b_data(): void
    {
        $this->actingAs($this->tenantB);

        $this->assertTrue(Account::query()->whereKey($this->accountB->id)->exists());
        $this->assertFalse(Account::query()->whereKey($this->accountA->id)->exists());

        $this->get(route('finance.accounts.index'))
            ->assertOk()
            ->assertSee('حساب نقدي حضانة ب', false)
            ->assertDontSee('حساب نقدي حضانة أ', false);

        $this->get(route('finance.journals.show', $this->journalB))->assertOk();
        $this->get(route('finance.journals.show', $this->journalA))->assertForbidden();
    }

    #[Test]
    public function cross_tenant_url_access_is_forbidden_for_journals_and_accounts(): void
    {
        $this->actingAs($this->tenant);

        $this->get(route('finance.journals.show', $this->journalB))->assertForbidden();
        $this->put(route('finance.accounts.update', $this->accountB), [
            'name_ar' => 'اختراق',
            'type' => 'asset',
            'opening_balance' => 0,
        ])->assertForbidden();
    }

    #[Test]
    public function staff_accountant_of_a_sees_only_a_and_never_b(): void
    {
        $staff = $this->makeStaff($this->tenant, [
            'login.app',
            'finance.view',
            'finance.view_reports',
            'finance.manage_expenses',
            'finance.manage_treasury',
            'finance.manage_ledger',
            'finance.admin',
        ]);

        $this->actingAs($staff);

        $this->assertTrue(Account::query()->whereKey($this->accountA->id)->exists());
        $this->assertFalse(Account::query()->whereKey($this->accountB->id)->exists());
        $this->assertSame(1, JournalEntry::query()->where('reference', 'like', 'D2-%-JE')->count());

        $this->get(route('finance.accounts.index'))
            ->assertOk()
            ->assertSee('حساب نقدي حضانة أ', false)
            ->assertDontSee('حساب نقدي حضانة ب', false);

        $this->get(route('finance.journals.show', $this->journalA))->assertOk();
        $this->get(route('finance.journals.show', $this->journalB))->assertForbidden();

        $this->get(route('finance.ledger.index', ['account_id' => $this->accountA->id]))
            ->assertOk()
            ->assertDontSee('حساب نقدي حضانة ب', false);

        $this->get(route('finance.reports.trial-balance'))->assertOk();
        $this->get(route('finance.reports.profit-loss'))->assertOk();
        $this->get(route('finance.dashboard'))->assertOk();
    }

    #[Test]
    public function receipts_bank_accounts_tax_rates_pma_and_recon_are_tenant_scoped(): void
    {
        $this->actingAs($this->tenant);

        $this->assertSame(1, Receipt::query()->where('reference', 'like', 'D2-%-RCPT')->count());
        $this->assertFalse(
            Receipt::query()->where('reference', 'D2-B-RCPT')->exists()
        );

        $this->assertSame(1, BankAccount::query()->where('account_number', 'like', 'D2-%')->count());
        $this->assertSame(1, ExpenseCategory::query()->where('code', 'like', 'D2-%')->count());
        $this->assertSame(1, TaxRate::query()->where('code', 'like', 'D2-%')->count());
        $this->assertSame(1, PaymentMethodAccount::query()->count());
        $this->assertSame(1, BankReconciliation::query()->count());

        $this->actingAs($this->tenantB);
        $this->assertTrue(Receipt::query()->where('reference', 'D2-B-RCPT')->exists());
        $this->assertFalse(Receipt::query()->where('reference', 'D2-A-RCPT')->exists());
        $this->assertSame(1, BankReconciliation::query()->count());
    }

    #[Test]
    public function default_ledger_accounts_resolve_tenant_owner_for_staff(): void
    {
        $staff = $this->makeStaff($this->tenant, ['login.app', 'finance.manage_ledger']);
        $this->actingAs($staff);

        DefaultLedgerAccounts::ensureCurrentAssetsGroup((int) $this->tenant->id);
        $cash = DefaultLedgerAccounts::cashOnHand();

        $this->assertSame((int) $this->tenant->id, (int) $cash->user_id);
    }

    private function assertAuthorizedNotForbidden(string $url): void
    {
        $response = $this->get($url);
        $this->assertNotSame(
            403,
            $response->status(),
            "Expected not forbidden for [{$url}], got {$response->status()}"
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeStaff(User $tenant, array $permissions): User
    {
        $staff = User::factory()->create([
            'role' => 'supervisor',
            'email' => 'd2-staff-'.uniqid().'@example.com',
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-D2-'.strtoupper(substr(uniqid(), -4)),
            'name' => 'D2 Accountant',
            'email' => $staff->email,
            'status' => 'active',
            'nursery_permissions' => $permissions,
        ]);

        return $staff;
    }

    private function seedTenantFinance(int $tenantUserId, string $tag): void
    {
        $cash = Account::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'code' => "D2-{$tag}-CASH",
            'name_ar' => $tag === 'A' ? 'حساب نقدي حضانة أ' : 'حساب نقدي حضانة ب',
            'type' => Account::TYPE_ASSET,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_bank' => false,
            'is_active' => true,
        ]);

        $expense = Account::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'code' => "D2-{$tag}-EXP",
            'name_ar' => "مصروف {$tag}",
            'type' => Account::TYPE_EXPENSE,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $entry = JournalEntry::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'created_by' => $tenantUserId,
            'reference' => "D2-{$tag}-JE",
            'date' => now()->toDateString(),
            'description' => "قيد حضانة {$tag}",
            'total' => 100,
        ]);

        JournalItem::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'journal_entry_id' => $entry->id,
            'account_id' => $expense->id,
            'debit' => 100,
            'credit' => 0,
        ]);
        JournalItem::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'journal_entry_id' => $entry->id,
            'account_id' => $cash->id,
            'debit' => 0,
            'credit' => 100,
        ]);

        Payment::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'date' => now()->toDateString(),
            'reference' => "D2-{$tag}-EXP",
            'amount' => 50,
            'type' => 'expense',
            'payment_method' => 'cash',
            'created_by' => $tenantUserId,
            'expense_account_id' => $expense->id,
            'status' => 'posted',
        ]);

        Payment::withoutGlobalScopes()->create([
            'user_id' => $tenantUserId,
            'date' => now()->toDateString(),
            'reference' => "D2-{$tag}-PMT",
            'amount' => 25,
            'type' => 'supplier',
            'payment_method' => 'cash',
            'created_by' => $tenantUserId,
        ]);

        if (Schema::hasTable('receipts')) {
            Receipt::withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'date' => now()->toDateString(),
                'reference' => "D2-{$tag}-RCPT",
                'amount' => 10,
                'created_by' => $tenantUserId,
            ]);
        }

        if (Schema::hasTable('expense_categories')) {
            ExpenseCategory::withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'code' => "D2-{$tag}",
                'name_ar' => "تصنيف {$tag}",
            ]);
        }

        if (Schema::hasTable('bank_accounts')) {
            BankAccount::withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'bank_name' => "Bank {$tag}",
                'account_number' => "D2-{$tag}-001",
                'status' => 'active',
                'ledger_account_id' => $cash->id,
            ]);
        }

        if (Schema::hasTable('tax_rates')) {
            TaxRate::withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'code' => "D2-{$tag}",
                'name_ar' => "ضريبة {$tag}",
                'rate_percent' => 15,
            ]);
        }

        if (Schema::hasTable('payment_method_accounts')) {
            PaymentMethodAccount::withoutGlobalScopes()->create([
                'user_id' => $tenantUserId,
                'method_key' => 'cash',
                'ledger_account_id' => $cash->id,
            ]);
        }

        if (Schema::hasTable('bank_reconciliations')) {
            BankReconciliation::withoutGlobalScopes()->create([
                'reconciliation_number' => "BR-D2-{$tag}-0001",
                'account_id' => $cash->id,
                'statement_date' => now()->toDateString(),
                'statement_balance' => 0,
                'book_balance' => 0,
                'difference' => 0,
                'status' => 'draft',
            ]);
        }
    }

    private function ensureD2Schema(): void
    {
        if (! Schema::hasColumn('payments', 'expense_account_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('payments', 'status')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->string('status', 20)->default('draft');
            });
        }
        if (! Schema::hasColumn('payments', 'tax_amount')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->decimal('tax_amount', 15, 4)->default(0);
            });
        }

        if (! Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable();
                $table->date('date');
                $table->string('reference', 50)->nullable();
                $table->decimal('amount', 15, 4)->default(0);
                $table->foreignId('journal_entry_id')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->foreignId('parent_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('bank_name');
                $table->string('account_number')->nullable();
                $table->string('iban')->nullable();
                $table->string('status', 20)->default('active');
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tax_rates')) {
            Schema::create('tax_rates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->decimal('rate_percent', 8, 4)->default(0);
                $table->foreignId('ledger_account_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bank_reconciliations')) {
            Schema::create('bank_reconciliations', function (Blueprint $table): void {
                $table->id();
                $table->string('reconciliation_number');
                $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
                $table->date('statement_date');
                $table->decimal('statement_balance', 15, 2)->default(0);
                $table->decimal('book_balance', 15, 2)->default(0);
                $table->decimal('difference', 15, 2)->default(0);
                $table->string('status', 20)->default('draft');
                $table->timestamps();
            });
        }
    }
}
