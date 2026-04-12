<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Existing expense rows (e.g. bulk import) are treated as already processed before the draft/approval workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('payments')) {
            return;
        }

        DB::table('payments')
            ->where('type', 'expense')
            ->update(['status' => 'posted']);
    }

    public function down(): void
    {
        // Intentionally empty: cannot safely restore prior per-row statuses.
    }
};
