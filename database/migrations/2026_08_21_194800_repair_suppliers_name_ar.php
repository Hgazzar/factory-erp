<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers') && ! Schema::hasColumn('suppliers', 'name_ar')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('name_ar')->nullable()->after('name');
            });
            DB::table('suppliers')->whereNull('name_ar')->update([
                'name_ar' => DB::raw('name'),
            ]);
        }

        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'name_ar')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('name_ar')->nullable();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
