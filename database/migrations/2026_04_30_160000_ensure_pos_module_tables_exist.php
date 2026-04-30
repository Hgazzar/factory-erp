<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * يضمن وجود جداول نقاط البيع (PostgreSQL وغيره) إذا فُوِّتت هجرات أبريل 2026 أو فشل جزء منها.
 * آمن لإعادة التشغيل: لا يُنشئ الجداول إلا عند غياب pos_devices.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_devices')) {
            $this->ensurePosIntegrationColumns();

            return;
        }

        if (! Schema::hasTable('warehouses')) {
            return;
        }

        Schema::create('pos_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mac_address', 64);
            $table->string('status', 32)->default('active');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'mac_address']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->decimal('closing_balance', 15, 4)->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['pos_device_id', 'status']);
        });

        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->string('receipt_number', 32);
            $table->decimal('total_price', 15, 4)->default(0);
            $table->string('payment_method', 32)->default('cash');
            $table->string('status', 32)->default('completed');
            $table->timestamps();

            $table->unique(['user_id', 'receipt_number']);
            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('pos_sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();

            $table->index('pos_sale_id');
        });

        $this->ensurePosIntegrationColumns();
    }

    /**
     * أعمدة الربط بالـ ERP (نسخة idempotent من 2026_04_28_140000_pos_erp_integration_columns).
     */
    private function ensurePosIntegrationColumns(): void
    {
        if (Schema::hasTable('pos_sessions')
            && Schema::hasTable('employees')
            && ! Schema::hasColumn('pos_sessions', 'employee_id')) {
            Schema::table('pos_sessions', function (Blueprint $table) {
                $table->foreignId('employee_id')->nullable()->after('pos_device_id')->constrained('employees')->nullOnDelete();
                $table->index(['employee_id']);
            });
        }

        if (Schema::hasTable('pos_sessions')
            && Schema::hasTable('production_shifts')
            && ! Schema::hasColumn('pos_sessions', 'production_shift_id')) {
            Schema::table('pos_sessions', function (Blueprint $table) {
                $table->foreignId('production_shift_id')->nullable()->after('employee_id')->constrained('production_shifts')->nullOnDelete();
                $table->index(['production_shift_id']);
            });
        }

        if (Schema::hasTable('pos_sales') && ! Schema::hasColumn('pos_sales', 'journal_entry_id')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->foreignId('journal_entry_id')->nullable()->after('status')->constrained('journal_entries')->nullOnDelete();
                $table->index(['journal_entry_id']);
            });
        }

        if (Schema::hasTable('pos_sale_lines') && ! Schema::hasColumn('pos_sale_lines', 'unit_cost')) {
            Schema::table('pos_sale_lines', function (Blueprint $table) {
                $table->decimal('unit_cost', 15, 4)->default(0)->after('line_total');
            });
        }
    }

    public function down(): void
    {
        // هجرة «تأكيد وجود» — لا تُسقِط الجداول تلقائياً لتجنّب فقدان بيانات الإنتاج.
    }
};
