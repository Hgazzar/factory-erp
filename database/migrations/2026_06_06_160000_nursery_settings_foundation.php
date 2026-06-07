<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursery_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nursery_name');
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('manager_mobile')->nullable();
            $table->string('manager_email')->nullable();
            $table->timestamps();
        });

        Schema::create('nursery_shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        if (Schema::hasTable('nursery_subscription_plans')) {
            Schema::table('nursery_subscription_plans', function (Blueprint $table): void {
                if (! Schema::hasColumn('nursery_subscription_plans', 'plan_type')) {
                    $table->string('plan_type', 32)->default('custom')->after('name');
                }
                if (! Schema::hasColumn('nursery_subscription_plans', 'currency_code')) {
                    $table->string('currency_code', 8)->default('SAR')->after('tax_rate');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nursery_subscription_plans')) {
            Schema::table('nursery_subscription_plans', function (Blueprint $table): void {
                if (Schema::hasColumn('nursery_subscription_plans', 'plan_type')) {
                    $table->dropColumn('plan_type');
                }
                if (Schema::hasColumn('nursery_subscription_plans', 'currency_code')) {
                    $table->dropColumn('currency_code');
                }
            });
        }

        Schema::dropIfExists('nursery_shifts');
        Schema::dropIfExists('nursery_settings');
    }
};
