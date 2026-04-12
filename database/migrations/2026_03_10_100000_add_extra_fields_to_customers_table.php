<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('tax_number', 50)->nullable()->after('email');
            $table->decimal('credit_limit', 15, 2)->nullable()->after('tax_number');
            $table->string('country', 100)->nullable()->after('address');
            $table->string('city', 100)->nullable()->after('country');
            $table->string('region', 100)->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('region');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar', 'tax_number', 'credit_limit',
                'country', 'city', 'region', 'postal_code',
            ]);
        });
    }
};
