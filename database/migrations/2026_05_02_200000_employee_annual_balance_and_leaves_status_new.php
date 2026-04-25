<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'leave_balance') && ! Schema::hasColumn('employees', 'annual_balance')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->decimal('annual_balance', 8, 2)->default(21);
            });
            if (Schema::hasColumn('employees', 'leave_balance')) {
                $rows = DB::table('employees')->select('id', 'leave_balance')->get();
                foreach ($rows as $row) {
                    DB::table('employees')
                        ->where('id', $row->id)
                        ->update(['annual_balance' => $row->leave_balance ?? 21]);
                }
                Schema::table('employees', function (Blueprint $table) {
                    $table->dropColumn('leave_balance');
                });
            }
        }

        if (Schema::hasTable('leaves')) {
            DB::table('leaves')->where('status', 'pending')->update(['status' => 'new']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'annual_balance') && ! Schema::hasColumn('employees', 'leave_balance')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->decimal('leave_balance', 8, 2)->default(21);
            });
            if (Schema::hasColumn('employees', 'leave_balance')) {
                $rows = DB::table('employees')->select('id', 'annual_balance')->get();
                foreach ($rows as $row) {
                    DB::table('employees')
                        ->where('id', $row->id)
                        ->update(['leave_balance' => $row->annual_balance ?? 21]);
                }
                Schema::table('employees', function (Blueprint $table) {
                    $table->dropColumn('annual_balance');
                });
            }
        }

        if (Schema::hasTable('leaves')) {
            DB::table('leaves')->where('status', 'new')->update(['status' => 'pending']);
        }
    }
};
