<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cost_centers')) {
            return;
        }

        if (! Schema::hasColumn('cost_centers', 'user_id')) {
            Schema::table('cost_centers', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->after('id')
                    ->default(1)
                    ->constrained('users')
                    ->restrictOnDelete();
            });
        }

        $defaultUserId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 1);
        DB::table('cost_centers')->whereNull('user_id')->update(['user_id' => $defaultUserId]);

        Schema::table('cost_centers', function (Blueprint $table) {
            try {
                $table->dropUnique(['code']);
            } catch (\Throwable) {
                try {
                    DB::statement('DROP INDEX IF EXISTS cost_centers_code_unique');
                } catch (\Throwable) {
                    //
                }
            }
        });

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cost_centers') || ! Schema::hasColumn('cost_centers', 'user_id')) {
            return;
        }

        Schema::table('cost_centers', function (Blueprint $table) {
            try {
                $table->dropUnique(['user_id', 'code']);
            } catch (\Throwable) {
                //
            }
        });

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
