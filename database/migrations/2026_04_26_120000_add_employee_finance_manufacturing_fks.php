<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط الموظفين بالمالية (مركز تكلفة + حساب أجور) وبأوامر الإنتاج (عامل مسؤول).
     */
    public function up(): void
    {
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (! Schema::hasColumn('employees', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')
                        ->nullable()
                        ->after('department_id')
                        ->constrained('cost_centers')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('employees', 'ledger_account_id')) {
                    $table->foreignId('ledger_account_id')
                        ->nullable()
                        ->after('cost_center_id')
                        ->constrained('accounts')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('manufacturing_runs')) {
            Schema::table('manufacturing_runs', function (Blueprint $table) {
                if (! Schema::hasColumn('manufacturing_runs', 'employee_id')) {
                    $table->foreignId('employee_id')
                        ->nullable()
                        ->after('user_id')
                        ->constrained('employees')
                        ->nullOnDelete()
                        ->comment('عامل/مسؤول الوردية أو الماكينة (اختياري)');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('manufacturing_runs') && Schema::hasColumn('manufacturing_runs', 'employee_id')) {
            Schema::table('manufacturing_runs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('employee_id');
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasColumn('employees', 'ledger_account_id')) {
                    $table->dropConstrainedForeignId('ledger_account_id');
                }
                if (Schema::hasColumn('employees', 'cost_center_id')) {
                    $table->dropConstrainedForeignId('cost_center_id');
                }
            });
        }
    }
};
