<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};

