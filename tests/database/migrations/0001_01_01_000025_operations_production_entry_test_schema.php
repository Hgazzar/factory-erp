<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('production_shifts')) {
            Schema::create('production_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->date('date');
                $table->string('status', 30)->default('planned');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('production_logs')) {
            Schema::table('production_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('production_logs', 'scrap_reason')) {
                    $table->string('scrap_reason', 100)->nullable();
                }
                if (! Schema::hasColumn('production_logs', 'downtime_reason')) {
                    $table->string('downtime_reason', 50)->nullable();
                }
                if (! Schema::hasColumn('production_logs', 'downtime_lost_hours')) {
                    $table->decimal('downtime_lost_hours', 8, 2)->nullable();
                }
            });
        }

        if (! Schema::hasTable('production_records')) {
            Schema::create('production_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable();
                $table->unsignedBigInteger('production_shift_id')->nullable();
                $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
                $table->decimal('quantity', 15, 4)->default(0);
                $table->decimal('scrap_quantity', 15, 4)->default(0);
                $table->string('scrap_reason', 100)->nullable();
                $table->timestamp('recorded_at')->nullable();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->string('downtime_reason', 50)->nullable();
                $table->decimal('downtime_lost_hours', 8, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_records');
        Schema::dropIfExists('production_shifts');
    }
};
