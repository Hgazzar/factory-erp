<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_cycles')) {
            return;
        }

        Schema::table('payroll_cycles', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_cycles', 'name')) {
                $table->string('name', 255)->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('payroll_cycles', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('departments')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('payroll_cycles', 'period_start')) {
                $table->date('period_start')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('payroll_cycles', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_cycles')) {
            return;
        }

        Schema::table('payroll_cycles', function (Blueprint $table): void {
            if (Schema::hasColumn('payroll_cycles', 'period_end')) {
                $table->dropColumn('period_end');
            }
            if (Schema::hasColumn('payroll_cycles', 'period_start')) {
                $table->dropColumn('period_start');
            }
            if (Schema::hasColumn('payroll_cycles', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
            if (Schema::hasColumn('payroll_cycles', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
