<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->default(0)->after('reference');
            $table->decimal('vat_amount', 15, 4)->default(0)->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'vat_amount']);
        });
    }
};

