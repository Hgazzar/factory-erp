<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('inventory_audits')
            ->whereIn('category', ['finished', 'semi_finished'])
            ->update(['category' => 'finished_good']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inventory_audits')
            ->where('category', 'finished_good')
            ->update(['category' => 'finished']);
    }
};
