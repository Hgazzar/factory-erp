<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('address')->comment('المدينة');
            $table->string('manager', 255)->nullable()->after('city')->comment('المسؤول');
            $table->string('phone', 50)->nullable()->after('manager')->comment('الهاتف');
            $table->string('map_location', 500)->nullable()->after('description')->comment('الموقع على الخريطة - رابط أو نص');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['city', 'manager', 'phone', 'map_location']);
        });
    }
};
