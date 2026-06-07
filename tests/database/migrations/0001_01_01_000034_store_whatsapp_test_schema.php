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
                    $table->timestamp('whatsapp_delivered_notified_at')->nullable();
                }
                if (! Schema::hasColumn('pos_sales', 'whatsapp_invoice_notified_at')) {
                    $table->timestamp('whatsapp_invoice_notified_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // test schema
    }
};
