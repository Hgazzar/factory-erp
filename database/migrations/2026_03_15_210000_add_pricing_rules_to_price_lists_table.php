<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->string('pricing_method', 30)->default('fixed')->after('type')->comment('fixed, margin');
            $table->decimal('default_margin_percent', 8, 2)->nullable()->after('pricing_method');
            $table->boolean('is_default')->default(false)->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropColumn(['pricing_method', 'default_margin_percent', 'is_default']);
        });
    }
};
