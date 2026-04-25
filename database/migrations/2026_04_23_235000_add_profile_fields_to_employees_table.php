<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->date('birth_date')->nullable()->after('last_name');
            $table->string('marital_status', 30)->nullable()->after('birth_date');
            $table->string('nationality', 100)->nullable()->after('marital_status');
            $table->string('id_number', 100)->nullable()->after('nationality');
            $table->string('passport_number', 100)->nullable()->after('id_number');
            $table->string('personal_email')->nullable()->after('email');
            $table->string('mobile', 50)->nullable()->after('personal_email');
            $table->string('phone', 50)->nullable()->after('mobile');
            $table->string('address')->nullable()->after('phone');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('region', 100)->nullable()->after('city');
            $table->string('postal_code', 30)->nullable()->after('region');
            $table->string('country', 100)->nullable()->after('postal_code');
            $table->string('emergency_contact_name')->nullable()->after('country');
            $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relation', 80)->nullable()->after('emergency_contact_phone');
            $table->string('employment_type', 30)->nullable()->after('position');
            $table->decimal('housing_allowance', 15, 2)->nullable()->after('base_salary');
            $table->decimal('transport_allowance', 15, 2)->nullable()->after('housing_allowance');
            $table->decimal('other_allowance', 15, 2)->nullable()->after('transport_allowance');
            $table->string('bank_name')->nullable()->after('ledger_account_id');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('iban', 50)->nullable()->after('bank_account_number');
            $table->string('social_insurance_number', 100)->nullable()->after('iban');
            $table->string('tax_number', 100)->nullable()->after('social_insurance_number');
            $table->string('insurance_number', 100)->nullable()->after('tax_number');
            $table->text('notes')->nullable()->after('insurance_number');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'birth_date',
                'marital_status',
                'nationality',
                'id_number',
                'passport_number',
                'personal_email',
                'mobile',
                'phone',
                'address',
                'city',
                'region',
                'postal_code',
                'country',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relation',
                'employment_type',
                'housing_allowance',
                'transport_allowance',
                'other_allowance',
                'bank_name',
                'bank_account_number',
                'iban',
                'social_insurance_number',
                'tax_number',
                'insurance_number',
                'notes',
            ]);
        });
    }
};
