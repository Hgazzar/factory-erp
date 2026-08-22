<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nursery_subscriptions') || Schema::hasColumn('nursery_subscriptions', 'renewed_from_id')) {
            return;
        }

        Schema::table('nursery_subscriptions', function (Blueprint $table): void {
            $table->foreignId('renewed_from_id')
                ->nullable()
                ->after('reversal_journal_entry_id')
                ->constrained('nursery_subscriptions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nursery_subscriptions') || ! Schema::hasColumn('nursery_subscriptions', 'renewed_from_id')) {
            return;
        }

        Schema::table('nursery_subscriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('renewed_from_id');
        });
    }
};
