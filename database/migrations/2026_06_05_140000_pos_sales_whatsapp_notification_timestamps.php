<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table): void {
                if (! Schema::hasColumn('pos_sales', 'whatsapp_delivered_notified_at')) {
                    $table->timestamp('whatsapp_delivered_notified_at')->nullable()->after('delivered_at');
                }
                if (! Schema::hasColumn('pos_sales', 'whatsapp_invoice_notified_at')) {
                    $table->timestamp('whatsapp_invoice_notified_at')->nullable()->after('whatsapp_delivered_notified_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table): void {
                foreach (['whatsapp_delivered_notified_at', 'whatsapp_invoice_notified_at'] as $col) {
                    if (Schema::hasColumn('pos_sales', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
