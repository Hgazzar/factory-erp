<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'vat_number')) {
                $table->string('vat_number', 50)->nullable()->after('tax_number');
            }
        });

        if (Schema::hasColumn('customers', 'vat_number') && Schema::hasColumn('customers', 'tax_number')) {
            DB::table('customers')
                ->whereNull('vat_number')
                ->whereNotNull('tax_number')
                ->update(['vat_number' => DB::raw('tax_number')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'vat_number')) {
                $table->dropColumn('vat_number');
            }
        });
    }
};
