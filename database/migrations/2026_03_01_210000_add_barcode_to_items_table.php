<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('barcode', 100)->nullable()->after('code')->comment('باركود الصنف للمسح');
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropColumn('barcode');
        });
    }
};
