<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TARGET = 15.0;

    private const FROM_MIN = 9.995;

    private const FROM_MAX = 10.005;

    public function up(): void
    {
        $updates = [
            ['quotation_items', 'tax_percent'],
            ['contract_items', 'tax_percent'],
            ['contracts', 'tax_percent'],
            ['purchase_order_items', 'tax_percent'],
            ['sales_order_items', 'tax_percent'],
            ['sales_invoice_items', 'tax_percent'],
            ['credit_note_items', 'tax_percent'],
            ['debit_note_items', 'tax_percent'],
            ['sales_return_items', 'tax_percent'],
            ['purchase_invoice_items', 'vat_percent'],
        ];

        foreach ($updates as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            DB::table($table)
                ->whereBetween($column, [self::FROM_MIN, self::FROM_MAX])
                ->update([$column => self::TARGET]);
        }

        foreach ([['sales_invoices', 'vat_rate'], ['purchase_invoices', 'vat_rate']] as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            DB::table($table)
                ->whereBetween($column, [self::FROM_MIN, self::FROM_MAX])
                ->update([$column => self::TARGET]);
        }
    }

    public function down(): void
    {
        // لا نعيد القيم تلقائياً لأن 15% قد تكون أصبحت مقصودة يدوياً.
    }
};
