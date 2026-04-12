<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('date');
            $table->decimal('paid_amount', 15, 4)->default(0)->after('total');
            $table->string('status', 30)->default('draft')->after('paid_amount')->comment('draft, unpaid, partial, paid, overdue');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'paid_amount', 'status']);
        });
    }
};
