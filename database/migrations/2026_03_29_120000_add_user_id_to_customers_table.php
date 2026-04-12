<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'code']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('code');
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
