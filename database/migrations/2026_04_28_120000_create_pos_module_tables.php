<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mac_address', 64);
            $table->string('status', 32)->default('active')->comment('active, inactive, maintenance');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'mac_address']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->decimal('closing_balance', 15, 4)->nullable();
            $table->string('status', 32)->default('open')->comment('open, closed');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['pos_device_id', 'status']);
        });

        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->string('receipt_number', 32);
            $table->decimal('total_price', 15, 4)->default(0);
            $table->string('payment_method', 32)->default('cash')->comment('cash, card, bank, mixed, other');
            $table->string('status', 32)->default('completed')->comment('completed, voided');
            $table->timestamps();

            $table->unique(['user_id', 'receipt_number']);
            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('pos_sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();

            $table->index('pos_sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_lines');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('pos_devices');
    }
};
