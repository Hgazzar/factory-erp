<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                if (! Schema::hasColumn('items', 'user_id')) {
                    $table->foreignId('user_id')
                        ->default(1)
                        ->after('id')
                        ->constrained('users')
                        ->restrictOnDelete();
                }
            });

            try {
                Schema::table('items', function (Blueprint $table) {
                    $table->dropUnique(['code']);
                });
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS items_code_unique');
                } catch (\Throwable) {
                    //
                }
            }

            Schema::table('items', function (Blueprint $table) {
                $table->unique(['user_id', 'code'], 'items_user_code_unique');
            });
        }

        if (Schema::hasTable('warehouses')) {
            Schema::table('warehouses', function (Blueprint $table) {
                if (! Schema::hasColumn('warehouses', 'user_id')) {
                    $table->foreignId('user_id')
                        ->default(1)
                        ->after('id')
                        ->constrained('users')
                        ->restrictOnDelete();
                }
            });

            try {
                Schema::table('warehouses', function (Blueprint $table) {
                    $table->dropUnique(['code']);
                });
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS warehouses_code_unique');
                } catch (\Throwable) {
                    //
                }
            }

            Schema::table('warehouses', function (Blueprint $table) {
                $table->unique(['user_id', 'code'], 'warehouses_user_code_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('items')) {
            try {
                Schema::table('items', function (Blueprint $table) {
                    $table->dropUnique('items_user_code_unique');
                });
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS items_user_code_unique');
                } catch (\Throwable) {
                    //
                }
            }

            if (Schema::hasColumn('items', 'user_id')) {
                Schema::table('items', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                });
            }

            Schema::table('items', function (Blueprint $table) {
                $table->unique('code');
            });
        }

        if (Schema::hasTable('warehouses')) {
            try {
                Schema::table('warehouses', function (Blueprint $table) {
                    $table->dropUnique('warehouses_user_code_unique');
                });
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS warehouses_user_code_unique');
                } catch (\Throwable) {
                    //
                }
            }

            if (Schema::hasColumn('warehouses', 'user_id')) {
                Schema::table('warehouses', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                });
            }

            Schema::table('warehouses', function (Blueprint $table) {
                $table->unique('code');
            });
        }
    }
};
