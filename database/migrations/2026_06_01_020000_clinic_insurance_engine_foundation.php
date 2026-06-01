<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clinic_insurance_companies')) {
            Schema::create('clinic_insurance_companies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'code']);
                $table->index(['user_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('clinic_insurance_plans')) {
            Schema::create('clinic_insurance_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('clinic_insurance_company_id')->constrained('clinic_insurance_companies')->cascadeOnDelete();
                $table->string('name');
                $table->decimal('copay_percent', 5, 2)->default(0);
                $table->decimal('max_copay_amount', 15, 4)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'clinic_insurance_company_id', 'is_active']);
            });
        }

        if (! Schema::hasColumn('patients', 'clinic_insurance_company_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->foreignId('clinic_insurance_company_id')->nullable()->after('medical_history_summary')
                    ->constrained('clinic_insurance_companies')->nullOnDelete();
                $table->foreignId('clinic_insurance_plan_id')->nullable()->after('clinic_insurance_company_id')
                    ->constrained('clinic_insurance_plans')->nullOnDelete();
                $table->string('insurance_card_number', 64)->nullable()->after('clinic_insurance_plan_id');
                $table->date('insurance_expires_at')->nullable()->after('insurance_card_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('patients', 'insurance_expires_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropConstrainedForeignId('clinic_insurance_plan_id');
                $table->dropConstrainedForeignId('clinic_insurance_company_id');
                $table->dropColumn(['insurance_card_number', 'insurance_expires_at']);
            });
        }

        Schema::dropIfExists('clinic_insurance_plans');
        Schema::dropIfExists('clinic_insurance_companies');
    }
};
