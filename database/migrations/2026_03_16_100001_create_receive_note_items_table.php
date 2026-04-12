<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receive_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receive_note_id')->constrained('receive_notes')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 500)->nullable()->comment('اسم/وصف الصنف إذا غير مرتبط ببند من الكتالوج');
            $table->decimal('quantity', 14, 4)->default(0);
            $table->string('unit', 50)->nullable()->comment('الوحدة: قطعة، كيلو، إلخ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receive_note_items');
    }
};
