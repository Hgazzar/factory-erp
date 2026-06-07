<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nursery_subscriptions')) {
            Schema::table('nursery_subscriptions', function (Blueprint $table): void {
                if (! Schema::hasColumn('nursery_subscriptions', 'journal_entry_id')) {
                    $table->foreignId('journal_entry_id')->nullable()->after('created_by')
                        ->constrained('journal_entries')->nullOnDelete();
                }
                if (! Schema::hasColumn('nursery_subscriptions', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('is_paid');
                }
                if (! Schema::hasColumn('nursery_subscriptions', 'payment_method')) {
                    $table->string('payment_method', 32)->nullable()->after('paid_at');
                }
            });
        }

        if (Schema::hasTable('employees') && Schema::hasTable('nursery_shifts')) {
            Schema::table('employees', function (Blueprint $table): void {
                if (! Schema::hasColumn('employees', 'nursery_shift_id')) {
                    $table->foreignId('nursery_shift_id')->nullable()->after('shift_id')
                        ->constrained('nursery_shifts')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'nursery_shift_id')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('nursery_shift_id');
            });
        }

        if (Schema::hasTable('nursery_subscriptions')) {
            Schema::table('nursery_subscriptions', function (Blueprint $table): void {
                if (Schema::hasColumn('nursery_subscriptions', 'journal_entry_id')) {
                    $table->dropConstrainedForeignId('journal_entry_id');
                }
                if (Schema::hasColumn('nursery_subscriptions', 'paid_at')) {
                    $table->dropColumn('paid_at');
                }
                if (Schema::hasColumn('nursery_subscriptions', 'payment_method')) {
                    $table->dropColumn('payment_method');
                }
            });
        }
    }
};
