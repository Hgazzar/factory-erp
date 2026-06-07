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
        // test schema
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
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

        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index],
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
};
