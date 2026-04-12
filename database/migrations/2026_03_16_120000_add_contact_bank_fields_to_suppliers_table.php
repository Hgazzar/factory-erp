<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('mobile', 50)->nullable()->after('phone');
            $table->string('website', 255)->nullable()->after('email');
            $table->string('name_ar', 255)->nullable()->after('name');
            $table->string('commercial_register', 100)->nullable()->after('tax_number');
            $table->decimal('credit_limit', 15, 4)->nullable()->after('commercial_register');
            $table->unsignedSmallInteger('payment_terms_days')->nullable()->after('credit_limit');
            $table->string('bank_name', 255)->nullable()->after('currency');
            $table->string('bank_account_number', 100)->nullable()->after('bank_name');
            $table->string('iban', 50)->nullable()->after('bank_account_number');
            $table->string('swift_code', 50)->nullable()->after('iban');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'mobile', 'website', 'name_ar', 'commercial_register',
                'credit_limit', 'payment_terms_days',
                'bank_name', 'bank_account_number', 'iban', 'swift_code',
            ]);
        });
    }
};
