<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        if ($this->suppliersHasUserCodeCompositeUnique()) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        if (Schema::hasColumn('suppliers', 'user_id')) {
            DB::table('suppliers')->whereNull('user_id')->update(['user_id' => 1]);
        }

        try {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropUnique(['code']);
            });
        } catch (\Throwable) {
            try {
                DB::statement('DROP INDEX IF EXISTS suppliers_code_unique');
            } catch (\Throwable) {
                //
            }
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $table->unique(['user_id', 'code'], 'suppliers_user_code_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        try {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropUnique('suppliers_user_code_unique');
            });
        } catch (\Throwable) {
            try {
                DB::statement('DROP INDEX IF EXISTS suppliers_user_code_unique');
            } catch (\Throwable) {
                //
            }
        }
    }

    private function suppliersHasUserCodeCompositeUnique(): bool
    {
        foreach (Schema::getIndexes('suppliers') as $index) {
            if (($index['unique'] ?? false) && ! ($index['primary'] ?? false)
                && ($index['columns'] ?? []) === ['user_id', 'code']) {
                return true;
            }
        }

        return false;
    }
};
