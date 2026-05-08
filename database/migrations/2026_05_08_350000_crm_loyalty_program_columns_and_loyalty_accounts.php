<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_loyalty_programs')) {
            Schema::table('crm_loyalty_programs', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_loyalty_programs', 'name_ar')) {
                    $table->string('name_ar', 180)->nullable()->after('name');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'description')) {
                    $table->text('description')->nullable()->after('name_ar');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'min_transaction_amount')) {
                    $table->decimal('min_transaction_amount', 12, 2)->default(0)->after('redemption_rate');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'min_redemption_points')) {
                    $table->decimal('min_redemption_points', 12, 2)->default(0)->after('min_transaction_amount');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'max_redemption_percentage')) {
                    $table->decimal('max_redemption_percentage', 5, 2)->nullable()->after('min_redemption_points');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'earn_on_discounts')) {
                    $table->boolean('earn_on_discounts')->default(false)->after('max_redemption_percentage');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'earn_on_tax')) {
                    $table->boolean('earn_on_tax')->default(false)->after('earn_on_discounts');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'has_expiration')) {
                    $table->boolean('has_expiration')->default(false)->after('earn_on_tax');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'start_date')) {
                    $table->date('start_date')->nullable()->after('has_expiration');
                }
                if (! Schema::hasColumn('crm_loyalty_programs', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
            });
        }

        if (! Schema::hasTable('crm_loyalty_accounts')) {
            Schema::create('crm_loyalty_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('loyalty_program_id')->constrained('crm_loyalty_programs')->cascadeOnDelete();
                $table->decimal('total_points', 14, 2)->default(0);
                $table->decimal('used_points', 14, 2)->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'customer_id', 'loyalty_program_id'], 'crm_loyalty_accounts_user_customer_program_unique');
                $table->index(['user_id', 'created_at']);
            });

            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE crm_loyalty_accounts ADD COLUMN current_balance DECIMAL(14,2) AS (total_points - used_points) STORED');
            } elseif ($driver === 'sqlite') {
                DB::statement('ALTER TABLE crm_loyalty_accounts ADD COLUMN current_balance REAL GENERATED ALWAYS AS (total_points - used_points) STORED');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE crm_loyalty_accounts ADD COLUMN current_balance numeric(14,2) GENERATED ALWAYS AS (total_points - used_points) STORED');
            } else {
                Schema::table('crm_loyalty_accounts', function (Blueprint $table) {
                    $table->decimal('current_balance', 14, 2)->default(0)->after('used_points');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_loyalty_accounts');

        if (Schema::hasTable('crm_loyalty_programs')) {
            $drops = [];
            foreach ([
                'name_ar',
                'description',
                'min_transaction_amount',
                'min_redemption_points',
                'max_redemption_percentage',
                'earn_on_discounts',
                'earn_on_tax',
                'has_expiration',
                'start_date',
                'end_date',
            ] as $col) {
                if (Schema::hasColumn('crm_loyalty_programs', $col)) {
                    $drops[] = $col;
                }
            }
            if ($drops !== []) {
                Schema::table('crm_loyalty_programs', function (Blueprint $table) use ($drops) {
                    $table->dropColumn($drops);
                });
            }
        }
    }
};
