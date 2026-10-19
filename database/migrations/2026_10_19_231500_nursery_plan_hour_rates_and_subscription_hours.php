<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nursery_subscription_plan_hour_rates')) {
            Schema::create('nursery_subscription_plan_hour_rates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('nursery_subscription_plans')->cascadeOnDelete();
                $table->unsignedTinyInteger('hours');
                $table->decimal('amount', 12, 2)->comment('السعر قبل الضريبة لهذا العدد من الساعات');
                $table->timestamps();

                $table->unique(['plan_id', 'hours']);
                $table->index(['user_id', 'plan_id']);
            });
        }

        if (Schema::hasTable('nursery_subscriptions') && ! Schema::hasColumn('nursery_subscriptions', 'hours')) {
            Schema::table('nursery_subscriptions', function (Blueprint $table): void {
                $table->unsignedTinyInteger('hours')->nullable()->after('plan_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nursery_subscriptions') && Schema::hasColumn('nursery_subscriptions', 'hours')) {
            Schema::table('nursery_subscriptions', function (Blueprint $table): void {
                $table->dropColumn('hours');
            });
        }

        Schema::dropIfExists('nursery_subscription_plan_hour_rates');
    }
};
