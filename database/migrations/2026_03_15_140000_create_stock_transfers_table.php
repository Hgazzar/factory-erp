<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique()->comment('رقم التحويل');
            $table->date('transfer_date')->comment('تاريخ التحويل');
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('المستودع المصدر');
            $table->foreignId('dest_warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('المستودع الوجهة');
            $table->string('status', 20)->default('completed')->comment('الحالة: completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
