<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * أعمدة ربط الحسابات في إعدادات المنشأة فقط.
     * أعمدة قيود اليومية على دورات الرواتب تُنشأ مع جدول payroll_cycles في هجرة لاحقة.
     */
    public function up(): void
    {
        if (Schema::hasTable('company_settings')) {
            Schema::table('company_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('company_settings', 'payroll_wage_expense_account_id')) {
                    $table->foreignId('payroll_wage_expense_account_id')
                        ->nullable()
                        ->constrained('accounts')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('company_settings', 'payroll_wages_payable_account_id')) {
                    $table->foreignId('payroll_wages_payable_account_id')
                        ->nullable()
                        ->constrained('accounts')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('company_settings', 'payroll_default_payment_account_id')) {
                    $table->foreignId('payroll_default_payment_account_id')
                        ->nullable()
                        ->constrained('accounts')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_settings')) {
            Schema::table('company_settings', function (Blueprint $table): void {
                if (Schema::hasColumn('company_settings', 'payroll_default_payment_account_id')) {
                    $table->dropConstrainedForeignId('payroll_default_payment_account_id');
                }
                if (Schema::hasColumn('company_settings', 'payroll_wages_payable_account_id')) {
                    $table->dropConstrainedForeignId('payroll_wages_payable_account_id');
                }
                if (Schema::hasColumn('company_settings', 'payroll_wage_expense_account_id')) {
                    $table->dropConstrainedForeignId('payroll_wage_expense_account_id');
                }
            });
        }
    }
};
