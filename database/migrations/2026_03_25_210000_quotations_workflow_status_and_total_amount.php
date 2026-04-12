<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        DB::statement("UPDATE quotations SET status = CASE status
            WHEN 'معلق' THEN 'draft'
            WHEN 'مقبول' THEN 'approved'
            WHEN 'مرفوض' THEN 'rejected'
            WHEN 'منتهي' THEN 'rejected'
            WHEN 'تمت الفوترة' THEN 'converted_to_order'
            ELSE status
        END");

        DB::statement("ALTER TABLE quotations ALTER COLUMN status SET DEFAULT 'draft'");

        if (Schema::hasColumn('quotations', 'total') && ! Schema::hasColumn('quotations', 'total_amount')) {
            DB::statement('ALTER TABLE quotations RENAME COLUMN total TO total_amount');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        if (Schema::hasColumn('quotations', 'total_amount') && ! Schema::hasColumn('quotations', 'total')) {
            DB::statement('ALTER TABLE quotations RENAME COLUMN total_amount TO total');
        }

        DB::statement("UPDATE quotations SET status = CASE status
            WHEN 'draft' THEN 'معلق'
            WHEN 'approved' THEN 'مقبول'
            WHEN 'rejected' THEN 'مرفوض'
            WHEN 'converted_to_order' THEN 'تمت الفوترة'
            ELSE status
        END");

        DB::statement("ALTER TABLE quotations ALTER COLUMN status SET DEFAULT 'معلق'");
    }
};
