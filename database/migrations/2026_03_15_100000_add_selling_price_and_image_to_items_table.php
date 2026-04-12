<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('selling_price', 15, 4)->nullable()->after('cost')->comment('سعر البيع');
            $table->string('image_path', 500)->nullable()->after('description')->comment('مسار صورة المنتج');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['selling_price', 'image_path']);
        });
    }
};
