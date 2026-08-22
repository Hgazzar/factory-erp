<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nursery_outbound_messages')) {
            Schema::create('nursery_outbound_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type', 64);
                $table->string('dedupe_key', 191)->unique();
                $table->string('status', 32)->default('queued');
                $table->unsignedInteger('attempts')->default(0);
                $table->json('payload')->nullable();
                $table->string('related_type', 64)->nullable();
                $table->unsignedBigInteger('related_id')->nullable();
                $table->string('provider_message_id', 128)->nullable();
                $table->text('error')->nullable();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['type', 'related_id']);
            });
        }

        if (Schema::hasTable('nursery_subscriptions') && ! Schema::hasColumn('nursery_subscriptions', 'reversal_journal_entry_id')) {
            Schema::table('nursery_subscriptions', function (Blueprint $table): void {
                $table->foreignId('reversal_journal_entry_id')
                    ->nullable()
                    ->constrained('journal_entries')
                    ->nullOnDelete();
            });
        }

        $this->backfillSentReminderOutbox('payment_reminder', 'payment_reminder_sent_at');
        $this->backfillSentReminderOutbox('renewal_reminder', 'renewal_reminder_sent_at');
    }

    public function down(): void
    {
        if (Schema::hasTable('nursery_subscriptions') && Schema::hasColumn('nursery_subscriptions', 'reversal_journal_entry_id')) {
            Schema::table('nursery_subscriptions', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('reversal_journal_entry_id');
            });
        }

        Schema::dropIfExists('nursery_outbound_messages');
    }

    private function backfillSentReminderOutbox(string $type, string $sentAtColumn): void
    {
        if (! Schema::hasTable('nursery_subscriptions') || ! Schema::hasTable('nursery_outbound_messages')) {
            return;
        }

        if (! Schema::hasColumn('nursery_subscriptions', $sentAtColumn)) {
            return;
        }

        $now = now();

        DB::table('nursery_subscriptions')
            ->whereNotNull($sentAtColumn)
            ->orderBy('id')
            ->each(function (object $row) use ($type, $sentAtColumn, $now): void {
                $dedupeKey = $type.':'.$row->user_id.':'.$row->id;
                $exists = DB::table('nursery_outbound_messages')->where('dedupe_key', $dedupeKey)->exists();
                if ($exists) {
                    return;
                }

                $sentAt = $row->{$sentAtColumn};

                DB::table('nursery_outbound_messages')->insert([
                    'user_id' => $row->user_id,
                    'type' => $type,
                    'dedupe_key' => $dedupeKey,
                    'status' => 'sent',
                    'attempts' => 1,
                    'payload' => json_encode(['subscription_id' => (int) $row->id], JSON_UNESCAPED_UNICODE),
                    'related_type' => 'subscription',
                    'related_id' => $row->id,
                    'queued_at' => $sentAt,
                    'sent_at' => $sentAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }
};
