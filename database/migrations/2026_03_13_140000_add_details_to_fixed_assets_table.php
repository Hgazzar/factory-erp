<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained('expense_categories')->nullOnDelete();
            $table->string('name_ar')->nullable()->after('name');
            $table->string('location')->nullable()->after('category_id');
            $table->text('description')->nullable()->after('location');

            $table->string('depreciation_method', 30)->nullable()->after('book_value');
            $table->unsignedInteger('useful_life_years')->nullable()->after('depreciation_method');
            $table->unsignedInteger('useful_life_months')->nullable()->after('useful_life_years');
            $table->date('depreciation_start_date')->nullable()->after('useful_life_months');
            $table->decimal('salvage_value', 14, 2)->nullable()->after('depreciation_start_date');

            $table->string('serial_number')->nullable()->after('salvage_value');
            $table->string('model')->nullable()->after('serial_number');
            $table->string('manufacturer')->nullable()->after('model');
            $table->date('warranty_end_date')->nullable()->after('manufacturer');
            $table->string('insurance_document')->nullable()->after('warranty_end_date');
            $table->date('insurance_end_date')->nullable()->after('insurance_document');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn([
                'name_ar',
                'location',
                'description',
                'depreciation_method',
                'useful_life_years',
                'useful_life_months',
                'depreciation_start_date',
                'salvage_value',
                'serial_number',
                'model',
                'manufacturer',
                'warranty_end_date',
                'insurance_document',
                'insurance_end_date',
            ]);
        });
    }
};
