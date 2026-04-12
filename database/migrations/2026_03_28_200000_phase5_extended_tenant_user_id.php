<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        $this->addUserIdColumn('sales_payments');
        $this->addUserIdColumn('stock_ins');
        $this->addUserIdColumn('inventory_audits');
        $this->addUserIdColumn('sales_returns');
        $this->addUserIdColumn('purchase_returns');
        $this->addUserIdColumn('contracts');
        $this->addUserIdColumn('inventory_transactions');

        $this->dropGlobalUniqueIfExists('stock_ins', 'stock_ins_document_number_unique', ['document_number']);
        $this->dropGlobalUniqueIfExists('inventory_audits', 'inventory_audits_audit_number_unique', ['audit_number']);
        $this->dropGlobalUniqueIfExists('contracts', 'contracts_contract_number_unique', ['contract_number']);

        $this->backfillUserIds($driver);

        foreach (['sales_payments', 'stock_ins', 'inventory_audits', 'sales_returns', 'purchase_returns', 'contracts', 'inventory_transactions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->whereNull('user_id')->update(['user_id' => 1]);
            }
        }

        if (Schema::hasTable('stock_ins') && Schema::hasColumn('stock_ins', 'user_id')) {
            Schema::table('stock_ins', function (Blueprint $table) {
                $table->unique(['user_id', 'document_number'], 'stock_ins_user_document_number_unique');
            });
        }
        if (Schema::hasTable('inventory_audits') && Schema::hasColumn('inventory_audits', 'user_id')) {
            Schema::table('inventory_audits', function (Blueprint $table) {
                $table->unique(['user_id', 'audit_number'], 'inventory_audits_user_audit_number_unique');
            });
        }
        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'user_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->unique(['user_id', 'contract_number'], 'contracts_user_contract_number_unique');
            });
        }
    }

    public function down(): void
    {
        //
    }

    private function addUserIdColumn(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'user_id')) {
                $blueprint->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });
    }

    private function dropGlobalUniqueIfExists(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropUnique($columns);
            });
        } catch (\Throwable) {
            try {
                DB::statement('DROP INDEX IF EXISTS '.$indexName);
            } catch (\Throwable) {
                //
            }
        }
    }

    private function backfillUserIds(string $driver): void
    {
        $this->updateFromRelation($driver, 'sales_payments', 'customer_id', 'customers');
        $this->updateFromRelation($driver, 'stock_ins', 'supplier_id', 'suppliers');
        $this->updateFromRelation($driver, 'inventory_audits', 'warehouse_id', 'warehouses');
        $this->updateFromRelation($driver, 'sales_returns', 'sales_invoice_id', 'sales_invoices');
        $this->updateFromRelation($driver, 'purchase_returns', 'supplier_id', 'suppliers');
        $this->updateFromRelation($driver, 'contracts', 'customer_id', 'customers');
        $this->updateFromRelation($driver, 'inventory_transactions', 'item_id', 'items');
    }

    /**
     * @param  string  $localFk  Foreign key column on $localTable pointing to $parentTable.id
     */
    private function updateFromRelation(string $driver, string $localTable, string $localFk, string $parentTable): void
    {
        if (! Schema::hasTable($localTable) || ! Schema::hasColumn($localTable, 'user_id')) {
            return;
        }
        if (! Schema::hasTable($parentTable) || ! Schema::hasColumn($parentTable, 'user_id')) {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE {$localTable} l SET user_id = p.user_id FROM {$parentTable} p WHERE l.{$localFk} = p.id AND l.{$localFk} IS NOT NULL");
        } elseif ($driver === 'mysql') {
            DB::statement("UPDATE {$localTable} l INNER JOIN {$parentTable} p ON l.{$localFk} = p.id SET l.user_id = p.user_id");
        } else {
            foreach (DB::table($localTable)->select('id', $localFk)->get() as $row) {
                $pid = $row->{$localFk} ?? null;
                if (! $pid) {
                    continue;
                }
                $uid = (int) (DB::table($parentTable)->where('id', $pid)->value('user_id') ?? 1);
                DB::table($localTable)->where('id', $row->id)->update(['user_id' => $uid]);
            }
        }
    }
};
