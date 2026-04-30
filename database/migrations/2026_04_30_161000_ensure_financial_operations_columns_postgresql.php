<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يضمن أعمدة المستأجر والربط المحاسبي المستخدمة في المسح المالي الجماعي، وتطهير الحسابات، ومسح المصروفات الدُفعي.
 * متوافق مع PostgreSQL (Laravel Schema). آمن لإعادة التشغيل عبر hasColumn.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUserIdOnTable('journal_entries');
        $this->ensureUserIdOnTable('journal_items');
        $this->ensureUserIdOnTable('payments');
        $this->ensureUserIdOnTable('bank_accounts');
        $this->ensureBankAccountsLedgerLink();
    }

    public function down(): void
    {
        // هجرة تأكيد — لا تُزال الأعمدة تلقائياً.
    }

    private function ensureUserIdOnTable(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'user_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $blueprint->foreignId('user_id')
                ->default(1)
                ->after('id')
                ->constrained('users')
                ->restrictOnDelete();
        });

        try {
            DB::table($table)->whereNull('user_id')->update(['user_id' => 1]);
        } catch (\Throwable) {
            //
        }
    }

    private function ensureBankAccountsLedgerLink(): void
    {
        if (! Schema::hasTable('bank_accounts') || ! Schema::hasTable('accounts')) {
            return;
        }

        if (Schema::hasColumn('bank_accounts', 'ledger_account_id')) {
            return;
        }

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('ledger_account_id')
                ->nullable()
                ->after('currency')
                ->constrained('accounts')
                ->restrictOnDelete();
        });
    }
};
