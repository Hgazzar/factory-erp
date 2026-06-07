<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_products') && ! Schema::hasColumn('pos_products', 'avg_cost')) {
            Schema::table('pos_products', function (Blueprint $table): void {
                $table->decimal('avg_cost', 16, 4)->nullable()->after('cost_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_products') && Schema::hasColumn('pos_products', 'avg_cost')) {
            Schema::table('pos_products', function (Blueprint $table): void {
                $table->dropColumn('avg_cost');
            });
        }
    }
};
