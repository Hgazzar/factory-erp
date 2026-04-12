<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique()->comment('رقم التسوية');
            $table->date('adjustment_date')->comment('تاريخ التسوية');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('المستودع');
            $table->string('type', 20)->comment('add = إضافة كمية, deduct = خصم كمية');
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
