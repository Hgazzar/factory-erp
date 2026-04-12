<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('supplier_type', 50)->nullable()->after('address');
            $table->unsignedTinyInteger('rating')->nullable()->after('supplier_type')->comment('1-5');
            $table->string('tax_number', 50)->nullable()->after('rating');
            $table->string('currency', 5)->nullable()->after('tax_number');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['supplier_type', 'rating', 'tax_number', 'currency']);
        });
    }
};
