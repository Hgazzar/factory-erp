<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->foreignId('commission_rule_id')->nullable()->after('sales_invoice_id')->constrained('commission_rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropForeign(['commission_rule_id']);
        });
    }
};
