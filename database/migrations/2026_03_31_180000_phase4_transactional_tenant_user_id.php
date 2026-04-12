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

        $this->addDepartmentsUserId();
        $this->migrateEmployeesToTenantUserId();
        $this->addUserIdColumn('journal_entries');
        $this->addUserIdColumn('journal_items');
        $this->addUserIdColumn('receipts');
        $this->addUserIdColumn('bank_accounts');
        $this->addUserIdColumn('sales_invoices');
        $this->addUserIdColumn('sales_invoice_items');
        $this->addUserIdColumn('purchase_invoices');
        $this->addUserIdColumn('purchase_invoice_items');
        $this->addUserIdColumn('delivery_orders');
        $this->addUserIdColumn('receive_notes');
        $this->addUserIdColumn('credit_notes');
        $this->addUserIdColumn('debit_notes');
        $this->addUserIdColumn('stock_movements');
        $this->addUserIdColumn('stock_transfers');
        $this->addUserIdColumn('stock_adjustments');
        $this->addUserIdColumn('item_warehouse');
        $this->addUserIdColumn('sales_payment_invoices');
        $this->addUserIdColumn('purchase_payment_invoices');

        $this->dropGlobalUniqueIfExists('delivery_orders', 'delivery_orders_delivery_number_unique', ['delivery_number']);
        $this->dropGlobalUniqueIfExists('stock_transfers', 'stock_transfers_transfer_number_unique', ['transfer_number']);
        $this->dropGlobalUniqueIfExists('stock_adjustments', 'stock_adjustments_adjustment_number_unique', ['adjustment_number']);
        $this->dropGlobalUniqueIfExists('credit_notes', 'credit_notes_note_number_unique', ['note_number']);
        $this->dropGlobalUniqueIfExists('debit_notes', 'debit_notes_note_number_unique', ['note_number']);
        $this->dropGlobalUniqueIfExists('bank_accounts', 'bank_accounts_account_number_unique', ['account_number']);
        $this->tryDropUniqueIndex('bank_accounts', 'bank_accounts_iban_unique');

        $this->dropCompositeUniqueIfExists('item_warehouse', 'item_warehouse_item_id_warehouse_id_unique', ['item_id', 'warehouse_id']);

        $this->tryDropUniqueIndex('employees', 'employees_code_unique');

        $this->backfillUserIds($driver);

        DB::table('journal_entries')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('journal_items')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('receipts')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('bank_accounts')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('sales_invoices')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('sales_invoice_items')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('purchase_invoices')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('purchase_invoice_items')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('delivery_orders')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('receive_notes')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('credit_notes')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('debit_notes')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('stock_movements')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('stock_transfers')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('stock_adjustments')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('item_warehouse')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('sales_payment_invoices')->whereNull('user_id')->update(['user_id' => 1]);
        DB::table('purchase_payment_invoices')->whereNull('user_id')->update(['user_id' => 1]);

        $this->addUniqueUnlessExists('delivery_orders', 'delivery_orders_user_delivery_number_unique', ['user_id', 'delivery_number']);
        $this->addUniqueUnlessExists('stock_transfers', 'stock_transfers_user_transfer_number_unique', ['user_id', 'transfer_number']);
        $this->addUniqueUnlessExists('stock_adjustments', 'stock_adjustments_user_adjustment_number_unique', ['user_id', 'adjustment_number']);
        $this->addUniqueUnlessExists('credit_notes', 'credit_notes_user_note_number_unique', ['user_id', 'note_number']);
        $this->addUniqueUnlessExists('debit_notes', 'debit_notes_user_note_number_unique', ['user_id', 'note_number']);
        $this->addUniqueUnlessExists('bank_accounts', 'bank_accounts_user_account_number_unique', ['user_id', 'account_number']);
        try {
            $this->addUniqueUnlessExists('bank_accounts', 'bank_accounts_user_iban_unique', ['user_id', 'iban']);
        } catch (\Throwable) {
            //
        }
        $this->addUniqueUnlessExists('item_warehouse', 'item_warehouse_user_item_warehouse_unique', ['user_id', 'item_id', 'warehouse_id']);
        $this->addUniqueUnlessExists('employees', 'employees_user_code_unique', ['user_id', 'code']);
    }

    public function down(): void
    {
        // Intentionally minimal: rolling back this phase safely requires manual steps.
    }

    private function addDepartmentsUserId(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'user_id')) {
            DB::table('departments')->whereNull('user_id')->update(['user_id' => 1]);
        }
    }

    private function migrateEmployeesToTenantUserId(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (! Schema::hasColumn('employees', 'linked_user_id') && Schema::hasColumn('employees', 'user_id')) {
            try {
                Schema::table('employees', function (Blueprint $table) {
                    $table->dropUnique(['user_id']);
                });
            } catch (\Throwable) {
                //
            }
            try {
                Schema::table('employees', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            } catch (\Throwable) {
                //
            }

            Schema::table('employees', function (Blueprint $table) {
                $table->renameColumn('user_id', 'linked_user_id');
            });

            Schema::table('employees', function (Blueprint $table) {
                $table->foreign('linked_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        } elseif (! Schema::hasColumn('employees', 'linked_user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('linked_user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'user_id')) {
                $table->foreignId('user_id')
                    ->default(1)
                    ->after('id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }
        });

        DB::table('employees')->whereNull('user_id')->update(['user_id' => 1]);
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

    private function tryDropUniqueIndex(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        try {
            DB::statement('DROP INDEX IF EXISTS '.$indexName);
        } catch (\Throwable) {
            //
        }
    }

    private function dropCompositeUniqueIfExists(string $table, string $indexName, array $columns): void
    {
        $this->dropGlobalUniqueIfExists($table, $indexName, $columns);
    }

    /**
     * Create a named unique constraint/index only if it does not already exist (safe for re-running the migration).
     */
    private function addUniqueUnlessExists(string $table, string $constraintName, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        if ($this->uniqueConstraintOrIndexExists($table, $constraintName)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($columns, $constraintName): void {
            $blueprint->unique($columns, $constraintName);
        });
    }

    private function uniqueConstraintOrIndexExists(string $table, string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        $physicalTable = Schema::getConnection()->getTablePrefix().$table;

        if ($driver === 'pgsql') {
            $rows = DB::select(
                'SELECT 1 FROM pg_constraint WHERE conname = ? LIMIT 1',
                [$name]
            );

            if (count($rows) > 0) {
                return true;
            }

            $idx = DB::select(
                'SELECT 1 FROM pg_indexes WHERE indexname = ? AND tablename = ? LIMIT 1',
                [$name, $physicalTable]
            );

            return count($idx) > 0;
        }

        if ($driver === 'mysql') {
            $database = Schema::getConnection()->getDatabaseName();
            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$database, $physicalTable, $name]
            );

            return count($rows) > 0;
        }

        $rows = DB::select(
            'SELECT 1 FROM sqlite_master WHERE type = ? AND name = ? AND tbl_name = ? LIMIT 1',
            ['index', $name, $physicalTable]
        );

        return count($rows) > 0;
    }

    private function backfillUserIds(string $driver): void
    {
        if (Schema::hasTable('journal_entries') && Schema::hasColumn('journal_entries', 'user_id')) {
            DB::table('journal_entries')->update(['user_id' => 1]);
        }

        if (Schema::hasTable('receipts') && Schema::hasColumn('receipts', 'user_id')) {
            foreach (DB::table('receipts')->select('id', 'customer_id', 'created_by')->get() as $row) {
                $uid = 1;
                if ($row->customer_id) {
                    $uid = (int) (DB::table('customers')->where('id', $row->customer_id)->value('user_id') ?? $row->created_by ?? 1);
                } elseif ($row->created_by) {
                    $uid = (int) $row->created_by;
                }
                DB::table('receipts')->where('id', $row->id)->update(['user_id' => $uid ?: 1]);
            }
        }

        if (Schema::hasTable('bank_accounts') && Schema::hasColumn('bank_accounts', 'user_id')) {
            foreach (DB::table('bank_accounts')->select('id', 'created_by')->get() as $row) {
                $uid = (int) ($row->created_by ?? 1) ?: 1;
                DB::table('bank_accounts')->where('id', $row->id)->update(['user_id' => $uid]);
            }
        }

        $this->updateFromRelation($driver, 'sales_invoices', 'customer_id', 'customers');
        $this->updateFromRelation($driver, 'purchase_invoices', 'supplier_id', 'suppliers');
        $this->updateFromRelation($driver, 'delivery_orders', 'sales_order_id', 'sales_orders');
        $this->updateFromRelation($driver, 'receive_notes', 'supplier_id', 'suppliers');
        $this->updateFromRelation($driver, 'credit_notes', 'customer_id', 'customers');
        $this->updateFromRelation($driver, 'debit_notes', 'supplier_id', 'suppliers');
        $this->updateFromRelation($driver, 'stock_transfers', 'source_warehouse_id', 'warehouses');
        $this->updateFromRelation($driver, 'stock_adjustments', 'warehouse_id', 'warehouses');
        $this->updateFromRelation($driver, 'stock_movements', 'warehouse_id', 'warehouses');
        $this->updateFromRelation($driver, 'item_warehouse', 'item_id', 'items');

        $this->updateFromRelation($driver, 'journal_items', 'journal_entry_id', 'journal_entries');
        $this->updateFromRelation($driver, 'sales_invoice_items', 'sales_invoice_id', 'sales_invoices');
        $this->updateFromRelation($driver, 'purchase_invoice_items', 'purchase_invoice_id', 'purchase_invoices');
        $this->updateFromRelation($driver, 'sales_payment_invoices', 'sales_invoice_id', 'sales_invoices');
        $this->updateFromRelation($driver, 'purchase_payment_invoices', 'payment_id', 'payments');
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
            DB::statement("UPDATE {$localTable} l SET user_id = p.user_id FROM {$parentTable} p WHERE l.{$localFk} = p.id");
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
