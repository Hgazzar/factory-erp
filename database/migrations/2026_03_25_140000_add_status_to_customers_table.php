<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('status', 20)->default('active');
        });

        if (Schema::hasColumn('customers', 'is_active')) {
            DB::table('customers')->where('is_active', false)->update(['status' => 'inactive']);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
