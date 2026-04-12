<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('customers')->truncate();
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // لا يمكن استرجاع البيانات بعد التصفير
    }
};
