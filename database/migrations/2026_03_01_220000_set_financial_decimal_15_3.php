<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PostgreSQL: توحيد الدقة المالية إلى DECIMAL(15,3).
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        $columns = [
            'journal_entries' => ['total'],
            'journal_items' => ['debit', 'credit'],
            'accounts' => ['opening_balance'],
            'receipts' => ['amount'],
            'payments' => ['amount'],
            'sales_invoices' => ['total', 'vat_amount'],
            'purchase_invoices' => ['total', 'vat_amount'],
            'items' => ['cost', 'min_stock'],
            'production_records' => ['quantity', 'scrap_quantity'],
            'production_logs' => ['quantity', 'rejected_quantity'],
            'item_warehouse' => ['quantity', 'reserved_quantity'],
            'sales_invoice_items' => ['quantity', 'unit_price', 'line_total'],
            'purchase_invoice_items' => ['quantity', 'unit_price', 'line_total'],
        ];

        foreach ($columns as $table => $fields) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }
            foreach ($fields as $col) {
                if (\Illuminate\Support\Facades\Schema::hasColumn($table, $col)) {
                    DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"{$col}\" TYPE DECIMAL(15,3) USING \"{$col}\"::decimal(15,3)");
                }
            }
        }

    }

    public function down(): void
    {
        // Revert to (15,4) if needed - optional
    }
};
