<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('date');
            $table->text('notes')->nullable()->after('reference');
            $table->text('internal_notes')->nullable()->after('notes');
            $table->text('terms')->nullable()->after('internal_notes');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'notes', 'internal_notes', 'terms']);
        });
    }
};
