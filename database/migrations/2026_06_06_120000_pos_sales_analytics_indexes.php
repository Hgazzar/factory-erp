<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_sales')) {
            return;
        }

        Schema::table('pos_sales', function (Blueprint $table): void {
            if (! $this->indexExists('pos_sales', 'pos_sales_tenant_channel_status_created_idx')) {
                $table->index(
                    ['user_id', 'sale_channel', 'status', 'created_at'],
                    'pos_sales_tenant_channel_status_created_idx',
                );
            }
            if (! $this->indexExists('pos_sales', 'pos_sales_tenant_channel_payment_created_idx')) {
                $table->index(
                    ['user_id', 'sale_channel', 'payment_method', 'created_at'],
                    'pos_sales_tenant_channel_payment_created_idx',
                );
            }
            if (! $this->indexExists('pos_sales', 'pos_sales_gateway_ref_channel_idx')) {
                $table->index(
                    ['payment_gateway_reference', 'sale_channel'],
                    'pos_sales_gateway_ref_channel_idx',
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pos_sales')) {
            return;
        }

        Schema::table('pos_sales', function (Blueprint $table): void {
            foreach ([
                'pos_sales_tenant_channel_status_created_idx',
                'pos_sales_tenant_channel_payment_created_idx',
                'pos_sales_gateway_ref_channel_idx',
            ] as $index) {
                if ($this->indexExists('pos_sales', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schema = $connection->getConfig('schema') ?? 'public';
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $rows = $connection->select("PRAGMA index_list('{$table}')");

            foreach ($rows as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        return match ($driver) {
            'pgsql' => (bool) $connection->selectOne(
                'select 1 from pg_indexes where schemaname = ? and tablename = ? and indexname = ?',
                [$schema, $table, $index],
            ),
            'mysql', 'mariadb' => (bool) $connection->selectOne(
                'select 1 from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ? limit 1',
                [$table, $index],
            ),
            default => false,
        };
    }
};
