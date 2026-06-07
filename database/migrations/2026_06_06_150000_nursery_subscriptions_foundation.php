<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursery_subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('nursery_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('nursery_children')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('nursery_subscription_plans')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('amount_after_tax', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->string('status', 24)->default('active');
            $table->timestamp('payment_reminder_sent_at')->nullable();
            $table->timestamp('renewal_reminder_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status', 'is_paid']);
            $table->index(['user_id', 'ends_on']);
            $table->index(['child_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_subscriptions');
        Schema::dropIfExists('nursery_subscription_plans');
    }
};
