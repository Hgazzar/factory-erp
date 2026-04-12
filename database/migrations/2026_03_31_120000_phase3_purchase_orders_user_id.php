<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            return;
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        if (! Schema::hasColumn('purchase_orders', 'order_number')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('order_number', 50)->nullable()->after('user_id');
            });
        }

        if (Schema::hasColumn('purchase_orders', 'order_number')) {
            try {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $table->dropUnique(['order_number']);
                });
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS purchase_orders_order_number_unique');
                } catch (\Throwable) {
                    //
                }
            }
        }

        foreach (DB::table('purchase_orders')->select('id', 'order_number')->orderBy('id')->get() as $row) {
            $num = $row->order_number;
            if ($num === null || $num === '') {
                $num = 'PO-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT);
            }
            DB::table('purchase_orders')->where('id', $row->id)->update([
                'order_number' => $num,
            ]);
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unique(['user_id', 'order_number'], 'purchase_orders_user_order_number_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            return;
        }

        try {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropUnique('purchase_orders_user_order_number_unique');
            });
        } catch (\Throwable) {
            try {
                DB::statement('DROP INDEX IF EXISTS purchase_orders_user_order_number_unique');
            } catch (\Throwable) {
                //
            }
        }

        if (Schema::hasColumn('purchase_orders', 'user_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasColumn('purchase_orders', 'order_number')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->unique('order_number', 'purchase_orders_order_number_unique');
            });
        }
    }
};
