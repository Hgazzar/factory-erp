<?php

use App\Models\Account;
use App\Models\BankAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_accounts') && ! Schema::hasColumn('bank_accounts', 'ledger_account_id')) {
            Schema::table('bank_accounts', function (Blueprint $table): void {
                $table->foreignId('ledger_account_id')
                    ->nullable()
                    ->after('currency')
                    ->constrained('accounts')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'bank_account_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->foreignId('bank_account_id')
                    ->nullable()
                    ->after('payment_method')
                    ->constrained('bank_accounts')
                    ->nullOnDelete();
            });
        }

        $this->backfillBankLedgerSubAccounts();
    }

    /**
     * لكل حساب بنكي بلا ربط دليل: إنشاء حساب أصول فرعي تحت 1020 ونقل الرصيد الافتتاحي من شاشة البنك إلى الرصيد الافتتاحي في الدليل (خيار محاسبي صرف).
     */
    private function backfillBankLedgerSubAccounts(): void
    {
        if (! Schema::hasTable('bank_accounts') || ! Schema::hasColumn('bank_accounts', 'ledger_account_id')) {
            return;
        }

        BankAccount::withoutGlobalScopes()
            ->whereNull('ledger_account_id')
            ->orderBy('id')
            ->each(function (BankAccount $ba): void {
                $uid = (int) $ba->user_id;
                if ($uid < 1) {
                    return;
                }

                $parent = Account::withoutGlobalScopes()
                    ->where('user_id', $uid)
                    ->where('code', '1020')
                    ->first();

                if (! $parent) {
                    return;
                }

                $opening = (float) ($ba->opening_balance ?? 0);
                $code = Account::generateNextNumericCodeForUser($uid, (int) $parent->id);

                $ledger = Account::withoutGlobalScopes()->create([
                    'user_id' => $uid,
                    'code' => $code,
                    'name_ar' => 'بنك — '.$ba->bank_name.' ('.$ba->account_number.')',
                    'name_en' => 'Bank — '.$ba->bank_name,
                    'type' => Account::TYPE_ASSET,
                    'parent_id' => $parent->id,
                    'opening_balance' => $opening,
                    'is_active' => true,
                    'is_bank' => true,
                    'allow_direct_posting' => true,
                ]);

                $ba->ledger_account_id = $ledger->id;
                $ba->opening_balance = 0;
                $ba->saveQuietly();
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'bank_account_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            });
        }

        if (Schema::hasTable('bank_accounts') && Schema::hasColumn('bank_accounts', 'ledger_account_id')) {
            Schema::table('bank_accounts', function (Blueprint $table): void {
                $table->dropForeign(['ledger_account_id']);
                $table->dropColumn('ledger_account_id');
            });
        }
    }
};
