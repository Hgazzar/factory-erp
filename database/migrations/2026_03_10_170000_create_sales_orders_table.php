<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('order_date');
            $table->date('expected_delivery')->nullable();
            $table->decimal('total', 15, 4)->default(0);
            $table->string('status', 50)->default('معلق'); // معلق | مكتمل | ملغي
            $table->string('reference', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
