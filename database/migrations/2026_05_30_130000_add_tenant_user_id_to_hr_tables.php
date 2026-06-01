<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultTenantId = (int) (DB::table('users')->where('role', 'admin')->orderBy('id')->value('id') ?? 1);

        if (Schema::hasTable('shifts') && ! Schema::hasColumn('shifts', 'user_id')) {
            Schema::table('shifts', function (Blueprint $blueprint): void {
                $blueprint->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $blueprint->index(['user_id']);
            });

            DB::table('shifts')->whereNull('user_id')->update(['user_id' => $defaultTenantId]);

            try {
                Schema::table('shifts', function (Blueprint $blueprint): void {
                    $blueprint->dropUnique(['code']);
                });
            } catch (\Throwable) {
            }

            Schema::table('shifts', function (Blueprint $blueprint): void {
                $blueprint->unique(['user_id', 'code'], 'shifts_user_code_unique');
            });
        }

        if (Schema::hasTable('pay_slips') && ! Schema::hasColumn('pay_slips', 'user_id')) {
            Schema::table('pay_slips', function (Blueprint $blueprint): void {
                $blueprint->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $blueprint->index(['user_id']);
            });

            foreach (DB::table('pay_slips')->select(['id', 'payroll_cycle_id'])->get() as $slip) {
                $tenantId = DB::table('payroll_cycles')->where('id', $slip->payroll_cycle_id)->value('user_id');
                if ($tenantId) {
                    DB::table('pay_slips')->where('id', $slip->id)->update(['user_id' => $tenantId]);
                }
            }

            DB::table('pay_slips')->whereNull('user_id')->update(['user_id' => $defaultTenantId]);
        }

        if (Schema::hasTable('payroll_items') && ! Schema::hasColumn('payroll_items', 'user_id')) {
            Schema::table('payroll_items', function (Blueprint $blueprint): void {
                $blueprint->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $blueprint->index(['user_id']);
            });

            foreach (DB::table('payroll_items')->select(['id', 'pay_slip_id'])->get() as $item) {
                $tenantId = DB::table('pay_slips')->where('id', $item->pay_slip_id)->value('user_id');
                if ($tenantId) {
                    DB::table('payroll_items')->where('id', $item->id)->update(['user_id' => $tenantId]);
                }
            }

            DB::table('payroll_items')->whereNull('user_id')->update(['user_id' => $defaultTenantId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_items') && Schema::hasColumn('payroll_items', 'user_id')) {
            Schema::table('payroll_items', function (Blueprint $blueprint): void {
                $blueprint->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasTable('pay_slips') && Schema::hasColumn('pay_slips', 'user_id')) {
            Schema::table('pay_slips', function (Blueprint $blueprint): void {
                $blueprint->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasTable('shifts') && Schema::hasColumn('shifts', 'user_id')) {
            try {
                Schema::table('shifts', function (Blueprint $blueprint): void {
                    $blueprint->dropUnique('shifts_user_code_unique');
                });
            } catch (\Throwable) {
            }

            Schema::table('shifts', function (Blueprint $blueprint): void {
                $blueprint->dropConstrainedForeignId('user_id');
            });

            Schema::table('shifts', function (Blueprint $blueprint): void {
                $blueprint->unique(['code']);
            });
        }
    }
};
